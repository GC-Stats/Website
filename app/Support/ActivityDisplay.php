<?php

/**
 * GC-Stats — Activity log display helpers
 *
 * Spatie activitylog descriptions are internal event codes (e.g.
 * "team.roster.member_added"), fine for the full activity log page's
 * <code> styling but not for a dashboard widget meant to read like plain
 * language. Maps known team/player event codes to a translated label;
 * anything unrecognized falls back to a humanized version of the raw code
 * rather than hiding it.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Support;

class ActivityDisplay
{
    private const LABEL_KEYS = [
        'team.information_updated' => 'information',
        'team.profile_updated' => 'information',
        'team.socials_updated' => 'socials',
        'team.tags_updated' => 'tags',
        'team.logo_updated' => 'logo',
        'team.logo_history_added' => 'logo',
        'team.logo_history_updated' => 'logo',
        'team.logo_history_removed' => 'logo',
        'team.roster.member_added' => 'roster',
        'team.roster.entry_updated' => 'roster',
        'team.roster.entry_removed' => 'roster',
        'team.max_permissions_updated' => 'permissions',
        'team.owner_assigned' => 'owner',
        'team.owner_removed' => 'owner',

        'player.information_updated' => 'information',
        'player.profile_updated' => 'information',
        'player.socials_updated' => 'socials',
        'player.logo_updated' => 'logo',
        'player.logo_history_added' => 'logo',
        'player.logo_history_updated' => 'logo',
        'player.logo_history_removed' => 'logo',
        'player.identifiers_updated' => 'identifiers',
        'player.val_id_reset' => 'identifiers',
        'player.discord_id_reset' => 'identifiers',
        'player.user_linked' => 'linked_user',
        'player.user_unlinked' => 'linked_user',
    ];

    public static function label(?string $description): string
    {
        $key = self::LABEL_KEYS[$description] ?? null;

        if ($key) {
            return __('admin.dashboard.activity_labels.'.$key);
        }

        return $description
            ? ucfirst(str_replace(['.', '_'], ' ', $description))
            : __('admin.dashboard.activity_labels.default');
    }
}
