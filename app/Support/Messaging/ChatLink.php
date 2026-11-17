<?php

namespace App\Support\Messaging;

use App\Models\Conversation;

/**
 * Link from a tenancy or job page to its chat, for the signed-in user's
 * inbox area. Null when messaging is off for this page or no chat exists;
 * the chat page itself still checks that the user is in the chat.
 */
final class ChatLink
{
    public static function to(string $kind, ?int $contextId, string $area): ?string
    {
        if (! $contextId || MessagingBadge::area() !== $area) {
            return null;
        }
        $conversation = Conversation::query()->where('kind', $kind)->where('context_id', $contextId)->first(['id', 'public_id']);

        return $conversation ? MessagingArea::route($area, 'show', $conversation) : null;
    }
}
