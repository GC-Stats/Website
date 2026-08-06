<?php

/**
 * GC-Stats — Plain-text linkifier
 *
 * Turns bare URLs inside an already-escaped block of user text into clickable
 * links. Shared by the About page and public user profiles (both render a
 * free-text bio field).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Support;

class TextLinkifier
{
    public static function linkify(?string $text): ?string
    {
        if (! $text) {
            return $text;
        }

        return preg_replace_callback(
            '/(https?:\/\/[^\s<]+)/i',
            function ($matches) {
                $url = rtrim($matches[1], '.,)');
                $trailing = substr($matches[1], strlen($url));

                return '<a href="'.$url.'" target="_blank" rel="noopener noreferrer nofollow" class="text-gc-yellow underline hover:no-underline break-all">'.$url.'</a>'.$trailing;
            },
            nl2br(e($text))
        );
    }
}
