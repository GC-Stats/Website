<?php

/**
 * GC-Stats — PermissionTeam helper
 *
 * spatie/laravel-permission's "teams" feature requires every role/permission
 * pivot row to carry a team id — there's no built-in concept of a "global"
 * (non-team) role once teams are enabled. We use team id 0 as that sentinel
 * (no `teams` row ever has id 0, and the pivot column carries no real FK
 * constraint, so this is safe) and switch the active team context through
 * this helper before touching roles.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;

class PermissionTeam
{
    public const GLOBAL_ID = 0;

    public static function global(): void
    {
        static::use(self::GLOBAL_ID);
    }

    public static function use(?int $teamId): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($teamId ?? self::GLOBAL_ID);

        Auth::user()?->unsetRelation('roles');
    }
}
