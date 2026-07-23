<?php

/**
 * GC-Stats — User role summary
 *
 * Shared "every role a user holds, grouped by which team/publisher it's
 * scoped to" query, used by both Admin\UserController's user detail page
 * and the public user profile page. Extracted so both read the exact same
 * model_has_roles join rather than drifting apart — see
 * tests/Feature/Admin/TeamPublisherRoleIsolationTest.php for why guard_name
 * must always be filtered alongside team_id (Team and NewsPublisher ids
 * share the same numeric space in that column).
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

class UserRoleSummary
{
    /**
     * Every role this user holds under the given guard, excluding the
     * global context (team_id 0 — see PermissionTeam), grouped by the
     * team/publisher id it's scoped to. Optionally narrowed to specific
     * role names.
     *
     * @return Collection<int, list<string>>
     */
    public static function rolesGroupedByTeam(int $userId, string $guard, string ...$roleNames): Collection
    {
        return DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_id', $userId)
            ->where('model_has_roles.model_type', User::class)
            ->where('roles.guard_name', $guard)
            ->where('model_has_roles.team_id', '!=', PermissionTeam::GLOBAL_ID)
            ->when($roleNames !== [], fn ($query) => $query->whereIn('roles.name', $roleNames))
            ->select('model_has_roles.team_id', 'roles.name')
            ->get()
            ->groupBy('team_id')
            ->map(fn ($rows) => $rows->pluck('name')->all());
    }
}
