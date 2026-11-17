<?php

namespace App\Support\Messaging;

use App\Models\Conversation;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

/**
 * Small presentation helpers for the chat screens: initials, a stable colour
 * per name, an icon per chat kind and friendly day labels.
 */
final class ChatAvatar
{
    /** Brand-safe avatar tones (background, text). */
    private const TONES = [
        ['#ffefe8', '#c2410c'],
        ['#e8ecf8', '#0f173a'],
        ['#e6f4ef', '#166534'],
        ['#f3e8ff', '#6b21a8'],
        ['#fef3c7', '#92400e'],
        ['#e0f2fe', '#075985'],
    ];

    public static function initials(?string $name): string
    {
        $name = trim((string) preg_replace('/\(.*?\)/', '', (string) $name));
        if ($name === '') {
            return '?';
        }
        $words = preg_split('/\s+/', $name) ?: [$name];
        $first = Str::substr($words[0], 0, 1);
        $last = count($words) > 1 ? Str::substr(end($words), 0, 1) : Str::substr($words[0], 1, 1);

        return Str::upper($first.$last);
    }

    /** Inline style for an avatar, the same colour every time for a name. */
    public static function style(?string $name): string
    {
        [$bg, $fg] = self::TONES[abs(crc32((string) $name)) % count(self::TONES)];

        return "background:{$bg};color:{$fg}";
    }

    public static function kindIcon(?string $kind): string
    {
        return match ($kind) {
            Conversation::KIND_REPAIR_JOB => 'bi-tools',
            Conversation::KIND_COMPLIANCE_JOB => 'bi-shield-check',
            default => 'bi-house-door',
        };
    }

    public static function kindClass(?string $kind): string
    {
        return match ($kind) {
            Conversation::KIND_REPAIR_JOB, Conversation::KIND_COMPLIANCE_JOB => 'is-job',
            default => 'is-home',
        };
    }

    public static function dayLabel(CarbonInterface $at): string
    {
        if ($at->isToday()) {
            return 'Today';
        }
        if ($at->isYesterday()) {
            return 'Yesterday';
        }

        return $at->isCurrentYear() ? $at->format('l j F') : $at->format('j F Y');
    }

    /** Short time for the chat list: 14:05 today, Mon this week, else 12 Nov. */
    public static function listTime(?CarbonInterface $at): string
    {
        if (! $at) {
            return '';
        }
        if ($at->isToday()) {
            return $at->format('H:i');
        }
        if ($at->isYesterday()) {
            return 'Yesterday';
        }
        if ($at->greaterThan(now()->subDays(6)->startOfDay())) {
            return $at->format('D');
        }

        return $at->isCurrentYear() ? $at->format('j M') : $at->format('j M Y');
    }
}
