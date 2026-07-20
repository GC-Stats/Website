<?php

/**
 * GC-Stats — Publisher scope lookup
 *
 * spatie/laravel-permission's `HasRoles::roles()` relation filters by
 * whatever team id is *currently active* (see PermissionTeam) — it cannot
 * answer "which publishers does this user belong to" when the caller
 * doesn't already know which publisher to switch context to. These queries
 * go straight at the model_has_roles/roles/permissions tables instead, so
 * they work regardless of the currently active PermissionTeam context.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PublisherScope
{
    /**
     * Every publisher id this user holds *any* role on (guard 'publisher'),
     * regardless of which permissions that role grants.
     *
     * @return Collection<int, int>
     */
    public static function publisherIdsForUser(int $userId): Collection
    {
        return DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_id', $userId)
            ->where('model_has_roles.model_type', User::class)
            ->where('roles.guard_name', PublisherPermissions::GUARD)
            ->distinct()
            ->pluck('model_has_roles.team_id');
    }

    /**
     * Every publisher id this user holds a role granting $permission on.
     *
     * @return Collection<int, int>
     */
    public static function publisherIdsWithPermission(int $userId, string $permission): Collection
    {
        return DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->join('role_has_permissions', 'role_has_permissions.role_id', '=', 'roles.id')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('model_has_roles.model_id', $userId)
            ->where('model_has_roles.model_type', User::class)
            ->where('roles.guard_name', PublisherPermissions::GUARD)
            ->where('permissions.name', $permission)
            ->distinct()
            ->pluck('model_has_roles.team_id');
    }

    /**
     * Every user id holding *any* role (guard 'publisher') on any of the
     * given publisher ids — used to scope the article-authoring "Author"
     * picker to people who actually belong to the publisher being posted
     * under, instead of listing every NewsAuthor on the site.
     *
     * @param  array<int>|Collection<int, int>  $publisherIds
     * @return Collection<int, int>
     */
    public static function userIdsForPublishers(array|Collection $publisherIds): Collection
    {
        if (count($publisherIds) === 0) {
            return collect();
        }

        return DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', User::class)
            ->where('roles.guard_name', PublisherPermissions::GUARD)
            ->whereIn('model_has_roles.team_id', $publisherIds)
            ->distinct()
            ->pluck('model_has_roles.model_id');
    }
}
