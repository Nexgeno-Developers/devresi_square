<?php

namespace App\Services;

/**
 * Allowlisted HTML sanitizer for rich-text fields (notes).
 * Not a full HTML Purifier — strips scripts, event handlers, and dangerous URLs.
 */
class HtmlSanitizer
{
    private const ALLOWED_TAGS = '<p><br><br/><strong><b><em><i><u><ul><ol><li><a><h1><h2><h3><blockquote><span>';

    public function sanitize(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        // Drop dangerous elements (including content).
        $html = preg_replace('#<(script|style|iframe|object|embed|link|meta|form|input|button|textarea|select)[^>]*>.*?</\1>#is', '', $html) ?? '';
        $html = preg_replace('#<(script|style|iframe|object|embed|link|meta|form|input|button|textarea|select)[^>]*/?>#is', '', $html) ?? '';

        // Strip inline event handlers (onerror=, onclick=, etc.).
        $html = preg_replace('/\son\w+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/iu', '', $html) ?? '';

        // Neutralize javascript:/data: URLs in href/src.
        $html = preg_replace('/\s(href|src)\s*=\s*(["\'])\s*(javascript|data)\s*:[^"\']*\2/iu', ' $1="#"', $html) ?? '';

        // Remove expression() / -moz-binding style tricks if style attrs sneak through.
        $html = preg_replace('/\sstyle\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/iu', '', $html) ?? '';

        $clean = strip_tags($html, self::ALLOWED_TAGS);

        // Ensure anchors only keep http(s)/mailto/relative hrefs.
        $clean = preg_replace_callback(
            '/<a\b([^>]*)>/iu',
            function (array $m) {
                $attrs = $m[1];
                if (! preg_match('/\bhref\s*=\s*(["\'])(.*?)\1/iu', $attrs, $href)) {
                    return '<a>';
                }
                $url = trim(html_entity_decode($href[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if ($url === '' || preg_match('{^(https?:|mailto:|/|#)}i', $url) !== 1) {
                    return '<a>';
                }

                return '<a href="'.e($url).'" rel="noopener noreferrer">';
            },
            $clean
        ) ?? $clean;

        return $clean;
    }
}
