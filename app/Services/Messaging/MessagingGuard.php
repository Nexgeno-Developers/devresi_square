<?php

namespace App\Services\Messaging;

use App\Models\MessageBlock;
use App\Models\MessagingPartyFlag;
use App\Support\Messaging\Party;

/** Blocks, pauses and suspensions. Used on every request, accept and send. */
class MessagingGuard
{
    public function isBlocked(Party $a, Party $b): bool
    {
        return MessageBlock::query()
            ->where(fn ($q) => $q->where('blocker_type', $a->type)->where('blocker_id', $a->id)->where('blocked_type', $b->type)->where('blocked_id', $b->id))
            ->orWhere(fn ($q) => $q->where('blocker_type', $b->type)->where('blocker_id', $b->id)->where('blocked_type', $a->type)->where('blocked_id', $a->id))
            ->exists();
    }

    public function hasBlocked(Party $blocker, Party $blocked): bool
    {
        return MessageBlock::query()
            ->where('blocker_type', $blocker->type)->where('blocker_id', $blocker->id)
            ->where('blocked_type', $blocked->type)->where('blocked_id', $blocked->id)
            ->exists();
    }

    public function assertCanSend(Party $party): void
    {
        $flag = MessagingPartyFlag::for($party);
        if ($flag?->isSuspended()) {
            throw new MessagingException('Messaging is switched off for you while ResiSquare reviews a report. Contact support if you think this is a mistake.');
        }
    }

    public function assertCanRequest(Party $party): void
    {
        $this->assertCanSend($party);
        $flag = MessagingPartyFlag::for($party);
        if ($flag?->isPaused()) {
            throw new MessagingException('New message requests are paused until '.$flag->paused_until->format('j M Y').' because several people turned yours down.');
        }
    }
}
