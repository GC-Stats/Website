<?php

/**
 * GC-Stats — Organization permission catalog
 *
 * Fixed set of permissions an organization's own roles can be granted,
 * defined once here by site admins. All roles/permissions created against
 * this catalog use the 'organization' guard (see config/auth.php) — a
 * namespace distinct from Team roles' 'web' guard, so the two systems can
 * never cross-grant a permission even if a Team/Organization row happens to
 * share a numeric id (all scoped through the same
 * App\Support\PermissionTeam::use($id) column).
 *
 * Each organization's roles (organization_owner/organization_editor, or
 * custom ones) get their own independent subset — one organization granting
 * organization_owner everything doesn't affect another organization's
 * organization_owner.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Support;

class OrganizationPermissions extends PermissionCatalog
{
    public const GUARD = 'organization';

    /**
     * @return array<string, list<string>> permission names grouped by section, for display
     */
    public static function grouped(): array
    {
        return [
            'profile' => ['organization.profile.edit', 'organization.logo.upload'],
            'staff' => ['organization.staff.view', 'organization.staff.edit', 'organization.staff.assignments.manage'],
            'news' => ['organization.news.view', 'organization.news.edit', 'organization.news.publish', 'organization.news.delete', 'organization.news.validate', 'organization.news.publish.unvalidated'],
            'media' => ['organization.media.view', 'organization.media.upload', 'organization.media.delete'],
            'streams' => ['organization.streams.view', 'organization.streams.edit', 'organization.streams.delete', 'organization.streams.link'],
            'vods' => ['organization.vods.link'],
            'api_keys' => ['organization.api-keys.view', 'organization.api-keys.manage'],
            'roles' => ['organization.roles.manage'],
        ];
    }

    /**
     * grouped(), narrowed to only the permissions in $ceiling (an
     * organization's own max_permissions) — groups left with nothing in
     * them are dropped.
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
