<?php

/**
 * GC-Stats — Email notification preferences
 *
 * Resolves/persists which notification categories a user wants to also
 * receive by email, opt-in (nothing is emailed until a category is enabled).
 * Stored the same way as CurrentTheme's theme choice — nested under the
 * `preferences` JSON column, this time under the 'email_notifications' key.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Support;

use App\Models\User;

class EmailNotificationPreferences
{
    public const CATEGORY_SANCTION = 'sanction';

    public const CATEGORY_CHANGE_REQUEST = 'change_request';

    public const CATEGORY_SOCIAL = 'social';

    public const CATEGORY_REPORT = 'report';

    public const CATEGORIES = [
        self::CATEGORY_SANCTION,
        self::CATEGORY_CHANGE_REQUEST,
        self::CATEGORY_SOCIAL,
        self::CATEGORY_REPORT,
    ];

    public static function enabled(User $user, string $category): bool
    {
        return (bool) ($user->preferences['email_notifications'][$category] ?? false);
    }

    /**
     * @param  array<int, string>  $enabledCategories
     */
    public static function update(User $user, array $enabledCategories): void
    {
        $settings = [];

        foreach (self::CATEGORIES as $category) {
            $settings[$category] = in_array($category, $enabledCategories, true);
        }

        $user->preferences = [...($user->preferences ?? []), 'email_notifications' => $settings];
        $user->save();
    }
}
