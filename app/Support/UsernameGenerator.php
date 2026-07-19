<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * GC-Stats — Username generator
 *
 * Derives a unique `users.username` from a display name / provider handle
 * for flows that don't collect one directly (e.g. first-time social login —
 * see SocialAuthController). Mirrors the backfill logic in migration
 * 0069_add_username_to_users_table.
 */
class UsernameGenerator
{
    public static function generate(?string $base): string
    {
        $slug = $base !== null ? substr(Str::slug($base, '_'), 0, 24) : '';
        $slug = $slug !== '' ? $slug : 'user';

        $username = $slug;

        while (User::where('username', $username)->exists()) {
            $username = $slug.'_'.Str::lower(Str::random(6));
        }

        return $username;
    }
}
