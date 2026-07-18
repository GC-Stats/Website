<?php

/**
 * GC-Stats — Role seeder
 *
 * Seeds the global (site-wide) roles and the fixed admin permission
 * catalog (see App\Support\AdminPermissions — one permission per action,
 * no user-created ones). Team-scoped roles (team_owner, team_manager,
 * team_editor) are created lazily per team by
 * TeamRoleService::ensureRolesExist() instead of being seeded up front —
 * with tens of thousands of teams already imported, most will never have a
 * claimed manager, so pre-creating three role rows for each would be dead
 * weight.
 */

namespace Database\Seeders;

use App\Support\AdminPermissions;
use App\Support\PermissionTeam;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        PermissionTeam::global();

        foreach (AdminPermissions::all() as $permission) {
            Permission::findOrCreate($permission);
        }

        // super-admin doesn't need explicit permissions — it bypasses every
        // ability check via Gate::before (see AppServiceProvider) — but the
        // role itself still needs to exist to be assignable.
        Role::findOrCreate('super-admin');

        Role::findOrCreate('moderator')->givePermissionTo([
            'reports.view', 'reports.resolve',
            'sanctions.view', 'sanctions.create', 'sanctions.revoke',
        ]);

        Role::findOrCreate('editor')->givePermissionTo(['news.manage']);
    }
}
