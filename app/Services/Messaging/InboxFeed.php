<?php

namespace App\Services\Messaging;

use App\Models\ComplianceJobMessage;
use App\Models\ComplianceJobOffer;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\RepairJobMessage;
use App\Models\RepairJobOffer;
use App\Models\User;
use App\Support\Messaging\MessagingArea;
use App\Support\Messaging\Party;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * One chat list: direct conversations plus repair and compliance job chats.
 * Job chats only exist between a landlord account and a contractor, so the
 * portal (tenants, contacts) sees direct conversations only.
 */
class InboxFeed
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function items(Party $as, User $by, string $area, bool $archived = false): Collection
    {
        $items = $this->direct($as, $by, $area, $archived);
        if (! $archived) {
            $items = $items->merge($this->repairJobs($as, $area))->merge($this->complianceJobs($as, $area));
        }

        return $items->sortByDesc(fn ($item) => $item['at']?->timestamp ?? 0)->values();
    }

    /** Unread count across direct and job chats, for the badge. */
    public function unreadTotal(Party $as, User $by, string $area, ConversationService $conversations): int
    {
        $total = $conversations->unreadCount($as, $by);
        if ($area === MessagingArea::ADMIN) {
            $total += RepairJobMessage::query()->whereNull('read_at')->where('side', RepairJobMessage::CONTRACTOR)
                ->whereHas('offer', fn ($q) => $q->where('account_id', $as->id))->count();
            $total += ComplianceJobMessage::query()->whereNull('read_at')->where('side', ComplianceJobMessage::CONTRACTOR)
                ->whereHas('offer', fn ($q) => $q->where('account_id', $as->id))->count();
        } elseif ($area === MessagingArea::CONTRACTOR) {
            $total += RepairJobMessage::query()->whereNull('read_at')->where('side', RepairJobMessage::LANDLORD)
                ->whereHas('offer', fn ($q) => $q->where('contractor_user_id', $as->id))->count();
            $total += ComplianceJobMessage::query()->whereNull('read_at')->where('side', ComplianceJobMessage::LANDLORD)
                ->whereHas('offer', fn ($q) => $q->where('contractor_user_id', $as->id))->count();
        }

        return $total;
    }

    private function direct(Party $as, User $by, string $area, bool $archived): Collection
    {
        $conversations = Conversation::query()
            ->whereHas('participants', function ($q) use ($as, $archived) {
                $q->where('party_type', $as->type)->where('party_id', $as->id)
                    ->when($archived, fn ($q) => $q->whereNotNull('archived_at'), fn ($q) => $q->whereNull('archived_at'));
            })
            ->with('participants')
            ->orderByDesc('last_message_at')
            ->limit(300)
            ->get();
        if ($conversations->isEmpty()) {
            return collect();
        }

        $lastMessages = Message::withTrashed()->whereIn('id', $conversations->pluck('last_message_id')->filter())->get()->keyBy('id');
        $unread = DB::table('messages as m')
            ->leftJoin('conversation_reads as r', function ($join) use ($as, $by) {
                $join->on('r.conversation_id', '=', 'm.conversation_id')->where('r.user_id', $by->id)->where('r.party_type', $as->type)->where('r.party_id', $as->id);
            })
            ->whereIn('m.conversation_id', $conversations->pluck('id'))
            ->whereNull('m.deleted_at')
            ->where(fn ($q) => $q->where('m.sender_party_type', '!=', $as->type)->orWhere('m.sender_party_id', '!=', $as->id))
            ->whereRaw('m.id > COALESCE(r.last_read_message_id, 0)')
            ->groupBy('m.conversation_id')
            ->selectRaw('m.conversation_id, COUNT(*) as unread')
            ->pluck('unread', 'conversation_id');

        return $conversations->map(function (Conversation $conversation) use ($as, $area, $lastMessages, $unread) {
            $other = $conversation->otherParty($as);
            $last = $lastMessages->get($conversation->last_message_id);

            return [
                'key' => 'c-'.$conversation->public_id,
                'kind' => 'direct',
                'title' => $other?->displayName() ?? 'Conversation',
                'label' => $other?->roleLabel() ?? '',
                'preview' => $this->preview($last),
                'at' => $conversation->last_message_at ?? $conversation->created_at,
                'unread' => (int) ($unread[$conversation->id] ?? 0),
                'url' => MessagingArea::route($area, 'show', $conversation),
                'read_only' => ! $conversation->isActive(),
            ];
        });
    }

    private function repairJobs(Party $as, string $area): Collection
    {
        $query = RepairJobOffer::query()->whereHas('messages')->with(['repairIssue.property', 'contractor.contractorProfile']);
        if ($area === MessagingArea::ADMIN) {
            $query->where('account_id', $as->id);
            $mySide = RepairJobMessage::LANDLORD;
        } elseif ($area === MessagingArea::CONTRACTOR) {
            $query->where('contractor_user_id', $as->id);
            $mySide = RepairJobMessage::CONTRACTOR;
        } else {
            return collect();
        }

        return $query->latest('updated_at')->limit(200)->get()->map(function (RepairJobOffer $offer) use ($area, $mySide) {
            $last = $offer->messages()->latest('id')->first();
            $unread = $offer->messages()->where('side', '!=', $mySide)->whereNull('read_at')->count();
            $ref = $offer->repairIssue?->reference_number ?: ('#'.$offer->repair_issue_id);

            return [
                'key' => 'rj-'.$offer->id,
                'kind' => 'repair',
                'title' => $area === MessagingArea::ADMIN
                    ? ($offer->contractor?->contractorProfile?->displayName() ?: ($offer->contractor?->name ?: 'Contractor'))
                    : (\App\Models\Account::query()->whereKey($offer->account_id)->value('account_name') ?: 'Landlord'),
                'label' => 'Job · '.$ref,
                'preview' => $last ? Str::limit((string) $last->body, 90) : '',
                'at' => $last?->created_at,
                'unread' => $unread,
                'url' => MessagingArea::route($area, 'jobs.repair', $offer),
                'read_only' => ! $offer->canMessage(),
            ];
        });
    }

    private function complianceJobs(Party $as, string $area): Collection
    {
        $query = ComplianceJobOffer::query()->whereHas('messages')->with(['booking.type', 'contractor.contractorProfile']);
        if ($area === MessagingArea::ADMIN) {
            $query->where('account_id', $as->id);
            $mySide = ComplianceJobMessage::LANDLORD;
        } elseif ($area === MessagingArea::CONTRACTOR) {
            $query->where('contractor_user_id', $as->id);
            $mySide = ComplianceJobMessage::CONTRACTOR;
        } else {
            return collect();
        }

        return $query->latest('updated_at')->limit(200)->get()->map(function (ComplianceJobOffer $offer) use ($area, $mySide) {
            $last = $offer->messages()->latest('id')->first();
            $unread = $offer->messages()->where('side', '!=', $mySide)->whereNull('read_at')->count();

            return [
                'key' => 'cj-'.$offer->id,
                'kind' => 'compliance',
                'title' => $area === MessagingArea::ADMIN
                    ? $offer->contractorName()
                    : (\App\Models\Account::query()->whereKey($offer->account_id)->value('account_name') ?: 'Landlord'),
                'label' => 'Check · '.($offer->booking?->type?->name ?? 'Compliance'),
                'preview' => $last ? Str::limit((string) $last->body, 90) : '',
                'at' => $last?->created_at,
                'unread' => $unread,
                'url' => MessagingArea::route($area, 'jobs.compliance', $offer),
                'read_only' => ! $offer->canMessage(),
            ];
        });
    }

    private function preview(?Message $message): string
    {
        if (! $message) {
            return '';
        }
        if ($message->trashed()) {
            return 'Message taken back';
        }
        if ($message->isRemoved()) {
            return 'Message removed by ResiSquare';
        }
        if (blank($message->body)) {
            return 'Sent a file';
        }

        return Str::limit((string) $message->body, 90);
    }
}
