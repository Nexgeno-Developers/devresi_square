<?php

namespace App\Services\Messaging;

use App\Models\ComplianceJobOffer;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\Property;
use App\Models\RepairJobOffer;
use App\Models\User;
use App\Support\Messaging\MessagingArea;
use App\Support\Messaging\Party;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/** The chat list and the names shown at the top of a chat. */
class InboxFeed
{
    public function __construct(private readonly ConversationService $conversations) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function items(Party $as, User $by, string $area, bool $archived = false): Collection
    {
        $conversations = Conversation::query()
            ->visibleTo($as)
            ->where('status', $archived ? Conversation::ARCHIVED : Conversation::ACTIVE)
            ->with(['participants', 'property'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->limit(300)
            ->get();
        if ($conversations->isEmpty()) {
            return collect();
        }

        $lastMessages = Message::query()->with('attachments')->whereIn('id', $conversations->pluck('last_message_id')->filter())->get()->keyBy('id');
        $unread = $this->conversations->unreadByConversation($as, $by, $conversations->pluck('id'));

        return $conversations->map(function (Conversation $conversation) use ($as, $area, $lastMessages, $unread) {
            $about = $this->describe($conversation, $as);

            return [
                'key' => $conversation->public_id,
                'kind' => $conversation->kind,
                'title' => $about['title'],
                'label' => $about['label'],
                'preview' => $this->preview($lastMessages->get($conversation->last_message_id)),
                'at' => $conversation->last_message_at ?? $conversation->created_at,
                'unread' => (int) ($unread[$conversation->id] ?? 0),
                'url' => MessagingArea::route($area, 'show', $conversation),
                'archived' => ! $conversation->isActive(),
            ];
        });
    }

    /**
     * Title, label and the people in the chat, from the viewer's side.
     *
     * @return array{title: string, label: string, people: Collection<int, array{name: string, role: string}>}
     */
    public function describe(Conversation $conversation, Party $viewer): array
    {
        $conversation->loadMissing(['participants', 'property']);
        $address = $conversation->property instanceof Property
            ? ($conversation->property->short_address ?: $conversation->property->full_address)
            : null;
        $people = $conversation->others($viewer)->map(fn (ConversationParticipant $p) => [
            'name' => $p->party()->displayName(),
            'role' => $p->roleLabel(),
        ])->values();
        $mine = $conversation->participantFor($viewer);

        if ($conversation->kind === Conversation::KIND_TENANCY) {
            if ($mine?->role === ConversationParticipant::TENANT) {
                $landlord = $conversation->participants->firstWhere('role', ConversationParticipant::LANDLORD);

                return [
                    'title' => $landlord ? $landlord->party()->displayName() : 'Your landlord',
                    'label' => ($landlord ? $landlord->roleLabel() : 'Landlord').($address ? ' · '.$address : ''),
                    'people' => $people,
                ];
            }

            $tenants = $people->where('role', 'Tenant')->pluck('name');

            return [
                'title' => $address ?: 'Tenancy',
                'label' => 'Tenancy'.($tenants->isNotEmpty() ? ' · '.Str::limit($tenants->implode(', '), 60) : ''),
                'people' => $people,
            ];
        }

        if ($conversation->kind === Conversation::KIND_REPAIR_JOB) {
            $offer = RepairJobOffer::query()->with('repairIssue')->find($conversation->context_id);
            $ref = $offer?->repairIssue?->reference_number ?: ('#'.$conversation->context_id);

            return ['title' => 'Repair '.$ref, 'label' => 'Job chat'.($address ? ' · '.$address : ''), 'people' => $people];
        }

        $offer = ComplianceJobOffer::query()->with('booking.type')->find($conversation->context_id);

        return [
            'title' => $offer?->booking?->type?->name ?: 'Compliance check',
            'label' => 'Job chat'.($address ? ' · '.$address : ''),
            'people' => $people,
        ];
    }

    private function preview(?Message $message): string
    {
        if (! $message) {
            return '';
        }
        if (blank($message->body)) {
            return $message->attachments->isNotEmpty() ? 'Sent a file' : '';
        }

        return Str::limit((string) $message->body, 90);
    }
}
