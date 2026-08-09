<?php

/**
 * GC-Stats — Organization scope lookup
 *
 * spatie/laravel-permission's `HasRoles::roles()` relation filters by
 * whatever team id is *currently active* (see PermissionTeam) — it cannot
 * answer "which organizations does this user belong to" when the caller
 * doesn't already know which organization to switch context to. These
 * queries go straight at the model_has_roles/roles/permissions tables
 * instead, so they work regardless of the currently active PermissionTeam
 * context.
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

class OrganizationScope
{
    /**
     * Every organization id this user holds *any* role on (guard
     * 'organization'), regardless of which permissions that role grants.
     *
     * @return Collection<int, int>
     */
    public static function organizationIdsForUser(int $userId): Collection
    {
        return DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_id', $userId)
            ->where('model_has_roles.model_type', User::class)
            ->where('roles.guard_name', OrganizationPermissions::GUARD)
            ->distinct()
            ->pluck('model_has_roles.team_id');
    }

    /**
     * Every organization id this user holds a role granting $permission on.
     *
     * @return Collection<int, int>
     */
    public static function organizationIdsWithPermission(int $userId, string $permission): Collection
    {
        return DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->join('role_has_permissions', 'role_has_permissions.role_id', '=', 'roles.id')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('model_has_roles.model_id', $userId)
            ->where('model_has_roles.model_type', User::class)
            ->where('roles.guard_name', OrganizationPermissions::GUARD)
            ->where('permissions.name', $permission)
            ->distinct()
            ->pluck('model_has_roles.team_id');
    }

    /**
     * Every user id holding *any* role (guard 'organization') on any of the
     * given organization ids — used to scope pickers (e.g. the article-
     * authoring "Author" picker) to people who actually belong to the
     * organization being acted under.
     *
     * @param  array<int>|Collection<int, int>  $organizationIds
     * @return Collection<int, int>
     */
    public static function userIdsForOrganizations(array|Collection $organizationIds): Collection
    {
        if (count($organizationIds) === 0) {
            return collect();
        }

        return DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', User::class)
            ->where('roles.guard_name', OrganizationPermissions::GUARD)
            ->whereIn('model_has_roles.team_id', $organizationIds)
            ->distinct()
            ->pluck('model_has_roles.model_id');
    }

    /**
     * Batched form of organizationIdsForUser() — every organization id each
     * of the given users holds a role on, in a single query, keyed by user
     * id. Used by admin listings that need this for many users at once
     * instead of looping organizationIdsForUser() per row.
     *
     * @param  array<int>|Collection<int, int>  $userIds
     * @return Collection<int, Collection<int, int>>
     */
    public static function organizationIdsForUsers(array|Collection $userIds): Collection
    {
        $userIds = collect($userIds)->values();

        if ($userIds->isEmpty()) {
            return collect();
        }

        return DB::table('model_has_roles')
            ->whereIn('model_has_roles.model_id', $userIds)
            ->where('model_has_roles.model_type', User::class)
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.guard_name', OrganizationPermissions::GUARD)
            ->select('model_has_roles.model_id', 'model_has_roles.team_id')
            ->distinct()
            ->get()
            ->groupBy('model_id')
            ->map(fn ($rows) => $rows->pluck('team_id'));
    }
}
