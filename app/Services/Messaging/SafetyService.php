<?php

namespace App\Services\Messaging;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageBlock;
use App\Models\MessageReport;
use App\Models\MessageRequest;
use App\Models\MessagingPartyFlag;
use App\Models\User;
use App\Support\AuditTrail;
use App\Support\Messaging\Party;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

/** Blocking, reporting and Super Admin moderation. */
class SafetyService
{
    public function __construct(
        private readonly MessageRequestService $requests,
        private readonly MessagingNotifier $notifier,
    ) {}

    public function block(Party $blocker, Party $blocked, User $by, ?string $reason = null): void
    {
        if (! $blocker->actsFor($by)) {
            abort(404);
        }
        if ($blocker->is($blocked)) {
            throw new MessagingException('You can’t block yourself.');
        }
        DB::transaction(function () use ($blocker, $blocked, $by, $reason) {
            MessageBlock::query()->firstOrCreate(
                ['blocker_type' => $blocker->type, 'blocker_id' => $blocker->id, 'blocked_type' => $blocked->type, 'blocked_id' => $blocked->id],
                ['blocked_by_user_id' => $by->id, 'reason' => $reason ? mb_substr($reason, 0, 500) : null]
            );
            // A waiting request from the blocked party is closed for good.
            MessageRequest::query()
                ->where('pair_key', Party::pairKey($blocker, $blocked))
                ->where('status', MessageRequest::PENDING)
                ->where('from_type', $blocked->type)->where('from_id', $blocked->id)
                ->update(['status' => MessageRequest::BLOCKED, 'decided_by_user_id' => $by->id, 'decided_at' => now()]);
        });
        $this->requests->checkMassDeclines($blocked);
    }

    public function unblock(Party $blocker, Party $blocked, User $by): void
    {
        if (! $blocker->actsFor($by)) {
            abort(404);
        }
        MessageBlock::query()
            ->where('blocker_type', $blocker->type)->where('blocker_id', $blocker->id)
            ->where('blocked_type', $blocked->type)->where('blocked_id', $blocked->id)
            ->delete();
    }

    public function report(Party $reporter, User $by, string $category, ?string $note, ?Conversation $conversation = null, ?Message $message = null, ?MessageRequest $request = null): MessageReport
    {
        if (! $reporter->actsFor($by)) {
            abort(404);
        }
        $key = 'messaging-reports:'.$by->id;
        if (RateLimiter::tooManyAttempts($key, (int) config('messaging.limits.reports_per_day'))) {
            throw new MessagingException('You have sent a lot of reports today. Our team will look at the ones you sent.');
        }
        RateLimiter::hit($key, 86400);

        $reported = $message?->senderParty()
            ?? ($request ? $request->fromParty() : null)
            ?? $conversation?->loadMissing('participants')->otherParty($reporter);
        if ($reported && $reported->is($reporter)) {
            throw new MessagingException('You can’t report your own messages.');
        }

        $report = MessageReport::create([
            'reporter_user_id' => $by->id,
            'reporter_party_type' => $reporter->type,
            'reporter_party_id' => $reporter->id,
            'reported_party_type' => $reported?->type,
            'reported_party_id' => $reported?->id,
            'conversation_id' => $conversation?->id ?? $message?->conversation_id,
            'message_id' => $message?->id,
            'request_id' => $request?->id,
            'category' => $category,
            'note' => $note ? mb_substr($note, 0, 1000) : null,
            'status' => MessageReport::OPEN,
        ]);

        $this->notifier->alertAdmins(
            'Message reported ('.$category.')',
            'A '.$category.' report was filed about '.($reported?->displayName() ?? 'a conversation').'.',
            route('backend.saas.message-reports.show', $report),
        );

        return $report;
    }

    /**
     * What Super Admin may see: the reported message plus up to N messages
     * either side of it, or the reported request. Nothing else.
     *
     * @return Collection<int, Message>
     */
    public function moderationContext(MessageReport $report): Collection
    {
        if (! $report->conversation_id) {
            return collect();
        }
        $window = (int) config('messaging.moderation_context');
        $base = Message::withTrashed()->with('attachments')->where('conversation_id', $report->conversation_id);
        if (! $report->message_id) {
            return (clone $base)->orderByDesc('id')->limit($window * 2 + 1)->get()->sortBy('id')->values();
        }
        $before = (clone $base)->where('id', '<', $report->message_id)->orderByDesc('id')->limit($window)->get();
        $target = (clone $base)->whereKey($report->message_id)->get();
        $after = (clone $base)->where('id', '>', $report->message_id)->orderBy('id')->limit($window)->get();

        return $before->merge($target)->merge($after)->sortBy('id')->values();
    }

    public function resolve(MessageReport $report, User $admin, string $action, ?string $note, ?int $suspendDays = null): void
    {
        abort_unless($admin->isSuperAdmin(), 403);
        $before = $report->only(['status', 'outcome']);
        $party = $report->reportedParty();

        DB::transaction(function () use ($report, $admin, $action, $note, $suspendDays, $party) {
            switch ($action) {
                case 'remove':
                    if ($report->message) {
                        $report->message->forceFill(['removed_at' => now(), 'removed_by' => $admin->id])->save();
                    }
                    break;
                case 'warn':
                    if ($party) {
                        $this->notifier->warned($party, (string) $note);
                    }
                    break;
                case 'suspend':
                    if ($party) {
                        MessagingPartyFlag::query()->updateOrCreate(
                            ['party_type' => $party->type, 'party_id' => $party->id],
                            [
                                'suspended_until' => $suspendDays ? now()->addDays($suspendDays) : MessagingPartyFlag::untilReviewed(),
                                'suspended_reason' => $note ? mb_substr($note, 0, 500) : 'Suspended after a report.',
                            ]
                        );
                    }
                    break;
                case 'lift':
                    if ($party) {
                        MessagingPartyFlag::query()->where('party_type', $party->type)->where('party_id', $party->id)
                            ->update(['suspended_until' => null, 'paused_until' => null, 'suspended_reason' => null]);
                    }
                    break;
            }
            $report->forceFill([
                'status' => $action === 'dismiss' ? MessageReport::DISMISSED : MessageReport::ACTIONED,
                'outcome' => $action,
                'outcome_note' => $note ? mb_substr($note, 0, 1000) : null,
                'handled_by' => $admin->id,
                'handled_at' => now(),
            ])->save();
        });

        AuditTrail::record(null, $admin->id, 'messaging.report.'.$action, $report, $before, $report->only(['status', 'outcome']));
    }
}
