<?php

namespace App\Services\Messaging;

use App\Models\Conversation;
use App\Models\MessageBlock;
use App\Models\MessageRequest;
use App\Models\MessagingPartyFlag;
use App\Models\User;
use App\Support\ContactMasker;
use App\Support\Messaging\Party;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

/**
 * One request per pair of parties, ever, in either direction. The row is
 * reused when a sender is allowed to ask again, so the pair key stays unique.
 */
class MessageRequestService
{
    public function __construct(
        private readonly MessagingGuard $guard,
        private readonly MessagingNotifier $notifier,
        private readonly ConversationService $conversations,
        private readonly MessagingDirectory $directory,
    ) {}

    /** Returns the existing conversation if the two can already chat. */
    public function send(Party $from, Party $to, User $by, ?string $intro): MessageRequest|Conversation
    {
        $this->assertActor($from, $by);
        $this->guard->assertCanRequest($from);
        $outcome = $this->evaluate($from, $to);
        if ($outcome instanceof Conversation) {
            return $outcome;
        }
        $this->consumeLimits($from, $by);

        return $this->store($from, $to, $by, $intro, null, $outcome);
    }

    /**
     * Request by email address. The reply is the same whether or not the
     * address belongs to someone, so the form cannot be used to look people up.
     */
    public function sendByEmail(Party $from, User $by, string $email, ?string $intro): void
    {
        $this->assertActor($from, $by);
        $this->guard->assertCanRequest($from);
        $email = mb_strtolower(trim($email));
        $this->consumeLimits($from, $by);

        $target = $this->directory->partyForEmail($email);
        if ($target) {
            try {
                $outcome = $this->evaluate($from, $target);
                if (! $outcome instanceof Conversation) {
                    $this->store($from, $target, $by, $intro, $email, $outcome);

                    return;
                }
            } catch (MessagingException) {
                // Fall through: record a placeholder so nothing differs for the sender.
            }
        }

        try {
            MessageRequest::query()->firstOrCreate(
                ['pair_key' => Party::emailPairKey($from, $email)],
                [
                    'from_type' => $from->type,
                    'from_id' => $from->id,
                    'to_type' => Party::EMAIL,
                    'to_id' => null,
                    'to_label' => $email,
                    'sent_by_user_id' => $by->id,
                    'intro' => $this->cleanIntro($intro)[0],
                    'intro_masked' => true,
                    'status' => MessageRequest::PENDING,
                    'expires_at' => now()->addDays((int) config('messaging.guardrails.request_expires_days')),
                ]
            );
        } catch (QueryException) {
            // Duplicate click: already recorded.
        }
    }

    public function accept(MessageRequest $request, Party $as, User $by): Conversation
    {
        $this->assertActor($as, $by);
        if (! $request->isPending() || ! $request->isTo($as)) {
            throw new MessagingException('This request is no longer waiting for a reply.');
        }
        $from = $request->fromParty();
        if (! $from->isActive() || $this->guard->isBlocked($from, $as)) {
            throw new MessagingException('This request can’t be accepted any more.');
        }

        $conversation = DB::transaction(function () use ($request, $as, $by, $from) {
            $locked = MessageRequest::query()->lockForUpdate()->find($request->id);
            if (! $locked || ! $locked->isPending()) {
                throw new MessagingException('This request is no longer waiting for a reply.');
            }
            $conversation = $this->conversations->forPair($from, $as)
                ?? $this->conversations->create($from, $as, Conversation::ORIGIN_REQUEST, null, $locked->id, false);
            if (filled($locked->intro)) {
                $this->conversations->addIntro($conversation, $from, $locked->sent_by_user_id, (string) $locked->intro, (bool) $locked->intro_masked);
            }
            $locked->forceFill([
                'status' => MessageRequest::ACCEPTED,
                'decided_by_user_id' => $by->id,
                'decided_at' => now(),
                'conversation_id' => $conversation->id,
                'to_label' => null,
            ])->save();

            return $conversation;
        });

        $this->notifier->requestAccepted($request->refresh(), $conversation);

        return $conversation;
    }

    public function decline(MessageRequest $request, Party $as, User $by, string $status = MessageRequest::DECLINED): void
    {
        $this->assertActor($as, $by);
        if (! $request->isPending() || ! $request->isTo($as)) {
            throw new MessagingException('This request is no longer waiting for a reply.');
        }
        $request->forceFill([
            'status' => $status,
            'decline_count' => $request->decline_count + 1,
            'cooldown_until' => now()->addDays((int) config('messaging.guardrails.decline_cooldown_days')),
            'decided_by_user_id' => $by->id,
            'decided_at' => now(),
        ])->save();

        $this->checkMassDeclines($request->fromParty());
    }

    public function withdraw(MessageRequest $request, Party $as, User $by): void
    {
        $this->assertActor($as, $by);
        if (! $request->isPending() || ! $request->isFrom($as)) {
            throw new MessagingException('This request is no longer waiting for a reply.');
        }
        $request->forceFill([
            'status' => MessageRequest::WITHDRAWN,
            'cooldown_until' => now()->addDays((int) config('messaging.guardrails.withdraw_cooldown_days')),
            'decided_by_user_id' => $by->id,
            'decided_at' => now(),
        ])->save();
    }

    public function expireDue(): int
    {
        $count = 0;
        MessageRequest::query()
            ->where('status', MessageRequest::PENDING)
            ->where('expires_at', '<', now())
            ->chunkById(200, function ($requests) use (&$count) {
                foreach ($requests as $request) {
                    $request->forceFill([
                        'status' => MessageRequest::EXPIRED,
                        'cooldown_until' => now()->addDays((int) config('messaging.guardrails.expiry_cooldown_days')),
                    ])->save();
                    $count++;
                }
            });

        return $count;
    }

    /**
     * Opens (or reopens) a conversation without a request, for parties who
     * already work together: tenancy, awarded job, shared appointment.
     */
    public function openFromRelationship(Party $as, Party $other, User $by, string $origin, int $originId): Conversation
    {
        $this->assertActor($as, $by);
        if ($as->is($other)) {
            throw new MessagingException('You can’t message yourself.');
        }
        if (! $other->isActive()) {
            throw new MessagingException('This person can’t receive messages.');
        }
        if ($this->guard->isBlocked($as, $other)) {
            throw new MessagingException('You can’t message this person.');
        }

        return DB::transaction(function () use ($as, $other, $origin, $originId) {
            $conversation = $this->conversations->forPair($as, $other);
            if ($conversation) {
                if (! $conversation->isActive() || $conversation->origin !== Conversation::ORIGIN_REQUEST) {
                    $conversation->forceFill([
                        'origin' => $origin,
                        'origin_id' => $originId,
                        'status' => Conversation::ACTIVE,
                        'read_only_reason' => null,
                        'unmasked_at' => $conversation->unmasked_at ?? now(),
                    ])->save();
                }

                return $conversation->load('participants');
            }

            $pairKey = Party::pairKey($as, $other);
            $request = MessageRequest::query()->lockForUpdate()->where('pair_key', $pairKey)->first();
            $conversation = $this->conversations->create($as, $other, $origin, $originId, $request?->id, true);
            $attributes = [
                'status' => MessageRequest::AUTO,
                'conversation_id' => $conversation->id,
                'decided_at' => now(),
                'cooldown_until' => null,
            ];
            if ($request) {
                $request->forceFill($attributes)->save();
            } else {
                $request = MessageRequest::create(array_merge($attributes, [
                    'pair_key' => $pairKey,
                    'from_type' => $as->type,
                    'from_id' => $as->id,
                    'to_type' => $other->type,
                    'to_id' => $other->id,
                ]));
            }
            $conversation->forceFill(['request_id' => $request->id])->save();

            return $conversation;
        });
    }

    /**
     * @return Conversation|MessageRequest|null existing conversation, an existing row to reuse, or null to create one
     */
    private function evaluate(Party $from, Party $to): Conversation|MessageRequest|null
    {
        if ($from->is($to)) {
            throw new MessagingException('You can’t message yourself.');
        }
        if (! $to->isActive()) {
            throw new MessagingException('This person can’t receive messages.');
        }
        if ($this->guard->isBlocked($from, $to)) {
            throw new MessagingException('You can’t send a request to this person.');
        }
        $conversation = $this->conversations->forPair($from, $to);
        if ($conversation) {
            return $conversation;
        }

        $existing = MessageRequest::query()->where('pair_key', Party::pairKey($from, $to))->first();
        if (! $existing) {
            return null;
        }
        $mine = $existing->isFrom($from);

        return match ($existing->status) {
            MessageRequest::PENDING => throw new MessagingException($mine
                ? 'Your request is waiting for a reply.'
                : 'They have already asked to message you. Accept their request instead.'),
            MessageRequest::BLOCKED => throw new MessagingException('You can’t send a request to this person.'),
            MessageRequest::ACCEPTED, MessageRequest::AUTO => $existing,
            default => $this->reusable($existing, $mine),
        };
    }

    private function reusable(MessageRequest $existing, bool $mine): MessageRequest
    {
        if (! $mine) {
            // The person who turned a request down can always start one back.
            return $existing;
        }
        if ($existing->status === MessageRequest::DECLINED && $existing->decline_count >= 2) {
            throw new MessagingException('This person isn’t accepting requests from you.');
        }
        if ($existing->status === MessageRequest::EXPIRED && $existing->attempt >= 2) {
            throw new MessagingException('This person hasn’t replied to your requests, so you can’t ask again.');
        }
        if ($existing->cooldown_until?->isFuture()) {
            throw new MessagingException('You can ask again from '.$existing->cooldown_until->format('j M Y').'.');
        }

        return $existing;
    }

    private function store(Party $from, Party $to, User $by, ?string $intro, ?string $toLabel, ?MessageRequest $existing): MessageRequest
    {
        [$intro, $masked] = $this->cleanIntro($intro);
        $attributes = [
            'from_type' => $from->type,
            'from_id' => $from->id,
            'to_type' => $to->type,
            'to_id' => $to->id,
            'to_label' => $toLabel,
            'sent_by_user_id' => $by->id,
            'intro' => $intro,
            'intro_masked' => $masked,
            'status' => MessageRequest::PENDING,
            'cooldown_until' => null,
            'decided_by_user_id' => null,
            'decided_at' => null,
            'conversation_id' => null,
            'expires_at' => now()->addDays((int) config('messaging.guardrails.request_expires_days')),
        ];

        $request = DB::transaction(function () use ($existing, $attributes, $from, $to) {
            if ($existing) {
                $locked = MessageRequest::query()->lockForUpdate()->find($existing->id);
                $sameDirection = $locked->isFrom($from);
                if (in_array($locked->status, [MessageRequest::ACCEPTED, MessageRequest::AUTO], true)) {
                    // Conversation was removed; start fresh in this direction.
                    $sameDirection = false;
                }
                $locked->forceFill(array_merge($attributes, [
                    'attempt' => $sameDirection ? $locked->attempt + 1 : 1,
                    'decline_count' => $sameDirection ? $locked->decline_count : 0,
                ]))->save();

                return $locked;
            }

            try {
                return MessageRequest::create(array_merge($attributes, ['pair_key' => Party::pairKey($from, $to)]));
            } catch (QueryException) {
                throw new MessagingException('A request between you already exists.');
            }
        });

        $this->notifier->requestReceived($request);

        return $request;
    }

    /** @return array{0: ?string, 1: bool} */
    private function cleanIntro(?string $intro): array
    {
        $intro = trim((string) $intro);
        if ($intro === '') {
            return [null, false];
        }
        if (mb_strlen($intro) > (int) config('messaging.intro_max')) {
            throw new MessagingException('Keep your note under '.config('messaging.intro_max').' characters.');
        }

        return ContactMasker::mask($intro);
    }

    private function consumeLimits(Party $from, User $by): void
    {
        $limits = config('messaging.limits');
        $guard = config('messaging.guardrails');
        // email_verified_at is almost never set here (OTP sign-up), so account age decides "new".
        $isNew = (bool) $by->created_at?->gt(now()->subDays((int) $guard['new_login_days']));
        $checks = [
            ['messaging-req-hour:'.$from->key(), (int) $limits['requests_per_hour'], 3600],
            ['messaging-req-day:'.$from->key(), (int) ($isNew ? $limits['new_login_requests_per_day'] : $limits['requests_per_day']), 86400],
        ];
        foreach ($checks as [$key, $max]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                throw new MessagingException('You have sent a lot of requests. Try again later.');
            }
        }
        $pending = MessageRequest::query()->where('from_type', $from->type)->where('from_id', $from->id)->where('status', MessageRequest::PENDING)->count();
        if ($pending >= (int) $limits['max_pending_requests']) {
            throw new MessagingException('You have '.$pending.' requests waiting for a reply. Wait for some answers first.');
        }
        foreach ($checks as [$key, , $decay]) {
            RateLimiter::hit($key, $decay);
        }
    }

    private function assertActor(Party $party, User $by): void
    {
        if (! $party->actsFor($by)) {
            abort(404);
        }
    }

    /** Declines or blocks from many different people in a short time pause new requests. */
    public function checkMassDeclines(Party $sender): void
    {
        $guard = config('messaging.guardrails');
        $flag = MessagingPartyFlag::query()->firstOrNew(['party_type' => $sender->type, 'party_id' => $sender->id]);
        if ($flag->isPaused() || $flag->isSuspended()) {
            return;
        }
        $since = now()->subDays((int) $guard['mass_decline_window_days']);
        if ($flag->last_paused_at && $flag->last_paused_at->gt($since)) {
            $since = $flag->last_paused_at;
        }

        $declinedBy = MessageRequest::query()
            ->where('from_type', $sender->type)->where('from_id', $sender->id)
            ->whereIn('status', [MessageRequest::DECLINED, MessageRequest::BLOCKED])
            ->where('decided_at', '>=', $since)
            ->get(['to_type', 'to_id'])
            ->toBase()
            ->map(fn ($r) => $r->to_type.':'.$r->to_id);
        $blockedBy = MessageBlock::query()
            ->where('blocked_type', $sender->type)->where('blocked_id', $sender->id)
            ->where('created_at', '>=', $since)
            ->get(['blocker_type', 'blocker_id'])
            ->toBase()
            ->map(fn ($b) => $b->blocker_type.':'.$b->blocker_id);
        $distinct = $declinedBy->merge($blockedBy)->unique()->count();
        if ($distinct < (int) $guard['mass_decline_threshold']) {
            return;
        }

        $repeat = $flag->last_paused_at && $flag->last_paused_at->gt(now()->subDays((int) $guard['repeat_pause_window_days']));
        if ($repeat) {
            $flag->fill([
                'suspended_until' => MessagingPartyFlag::untilReviewed(),
                'suspended_reason' => 'Paused twice in '.$guard['repeat_pause_window_days'].' days for declined or blocked requests. Needs review.',
            ]);
        } else {
            $flag->fill([
                'paused_until' => now()->addDays((int) $guard['pause_days']),
                'last_paused_at' => now(),
                'pause_count' => (int) $flag->pause_count + 1,
            ]);
        }
        $flag->save();

        $this->notifier->alertAdmins(
            $repeat ? 'Messaging suspended: '.$sender->displayName() : 'Messaging paused: '.$sender->displayName(),
            $sender->displayName().' had requests declined or was blocked by '.$distinct.' people. '.($repeat ? 'Messaging is switched off until you review it.' : 'New requests are paused for '.$guard['pause_days'].' days.'),
            route('backend.saas.message-reports.index'),
            'messaging-pause:'.$sender->key()
        );
    }
}
