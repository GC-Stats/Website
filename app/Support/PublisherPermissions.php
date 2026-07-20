<?php

/**
 * GC-Stats — Publisher permission catalog
 *
 * Fixed set of permissions a publisher's own roles can be granted, defined
 * once here by site admins. All roles/permissions created against this
 * catalog use the 'publisher' guard (see config/auth.php) — a namespace
 * distinct from Team roles' default 'web' guard, so the two systems can
 * never cross-grant a permission even if a Team and a NewsPublisher happen
 * to share a numeric id (both are scoped through the same
 * App\Support\PermissionTeam::use($id) column).
 *
 * Each publisher's roles (publisher_owner/publisher_editor, or custom ones)
 * get their own independent subset via App\Http\Controllers\News\RoleController
 * — one publisher granting publisher_owner everything doesn't affect
 * another publisher's publisher_owner.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Support;

class PublisherPermissions extends PermissionCatalog
{
    public const GUARD = 'publisher';

    /**
     * @return array<string, list<string>> permission names grouped by section, for display
     */
    public static function grouped(): array
    {
        return [
            'profile' => ['publisher.profile.edit', 'publisher.logo.upload'],
            'news' => ['publisher.news.view', 'publisher.news.edit', 'publisher.news.publish', 'publisher.news.delete'],
            'media' => ['publisher.media.view', 'publisher.media.upload', 'publisher.media.delete'],
            'roles' => ['publisher.roles.manage'],
        ];
    }

    /**
     * grouped(), narrowed to only the permissions in $ceiling (a publisher's
     * own max_permissions) — groups left with nothing in them are dropped.
     *
     * @param  list<string>  $ceiling
     * @return array<string, list<string>>
     */
    public static function groupedWithin(array $ceiling): array
    {
        return collect(self::grouped())
            ->map(fn ($permissions) => array_values(array_intersect($permissions, $ceiling)))
            ->filter(fn ($permissions) => $permissions !== [])
            ->all();
    }
}
