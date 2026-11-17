<?php

namespace App\Support;

/**
 * Hides phone numbers, email addresses and links in free text, so people
 * cannot take a conversation off the platform before it is allowed.
 */
final class ContactMasker
{
    /**
     * @return array{0: string, 1: bool} masked text, and whether anything was hidden
     */
    public static function mask(string $body, string $hidden = '[hidden until you both reply]'): array
    {
        $count = 0;
        $body = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $hidden, $body, -1, $emails);
        $body = preg_replace('/\b(?:https?:\/\/|www\.)\S+/i', $hidden, $body, -1, $links);
        $body = preg_replace_callback('/\+?\d[\d\s().-]{7,}\d/', function ($match) use ($hidden, &$count) {
            if (strlen(preg_replace('/\D/', '', $match[0])) < 10) {
                return $match[0];
            }
            $count++;

            return $hidden;
        }, $body);

        return [$body, ($emails + $links + $count) > 0];
    }
}
