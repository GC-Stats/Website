<?php

/**
 * GC-Stats — Social link display config
 *
 * Maps a "socials" JSON field's platform keys (see User::socials,
 * AboutTeamMember's former equivalent) to the URL prefix and icon used to
 * render them. Shared by the About page team cards and public user profiles.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Support;

use Illuminate\Support\Collection;

class SocialLinkConfig
{
    /**
     * @return Collection<string, array{url: string, icon: string}>
     */
    public static function map(): Collection
    {
        return collect([
            'twitter' => ['url' => 'https://x.com/', 'icon' => 'fab-x-twitter'],
            'twitch' => ['url' => 'https://twitch.tv/', 'icon' => 'fab-twitch'],
            'tiktok' => ['url' => 'https://tiktok.com/@', 'icon' => 'fab-tiktok'],
            'instagram' => ['url' => 'https://instagram.com/', 'icon' => 'fab-instagram'],
            'youtube' => ['url' => 'https://youtube.com/@', 'icon' => 'fab-youtube'],
            'discord' => ['url' => '#', 'icon' => 'fab-discord'],
            'email' => ['url' => 'mailto:', 'icon' => 'fas-envelope'],
        ]);
    }
}
