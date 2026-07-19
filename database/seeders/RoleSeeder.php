<?php

/**
 * GC-Stats — Role seeder
 *
 * Seeds global roles and both permission catalogs (see
 * App\Support\AdminPermissions and App\Support\TeamPermissions — the
 * latter isn't team-scoped itself, only the roles that use it are, see
 * TeamPermissions' docblock). Per-team roles are seeded lazily by
 * TeamRoleService instead, not here.
 */

namespace Database\Seeders;

use App\Support\AdminPermissions;
use App\Support\PermissionTeam;
use App\Support\TeamPermissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        PermissionTeam::global();

        $catalog = [...AdminPermissions::all(), ...TeamPermissions::all()];

        foreach ($catalog as $permission) {
            Permission::findOrCreate($permission);
        }

        // Permissions from a retired catalog entry (e.g. the old
        // 'site.moderate'/'sanction.issue'/'sanction.revoke' names, before
        // the one-permission-per-action split) would otherwise linger in
        // the `permissions` table and role_has_permissions forever —
        // prune anything not in either current catalog so no permission
        // matrix ever shows a checkbox nothing checks.
        Permission::whereNotIn('name', $catalog)->get()->each->delete();

        // super-admin doesn't need explicit permissions — it bypasses every
        // ability check via Gate::before (see AppServiceProvider) — but the
        // role itself still needs to exist to be assignable.
        Role::findOrCreate('super-admin');

        // syncPermissions() rather than givePermissionTo(): re-running this
        // seeder always leaves each role holding exactly this list, so a
        // permission renamed or removed from the catalog can't linger on a
        // role that no longer should have it.
        Role::findOrCreate('moderator')->syncPermissions([
            'reports.view', 'reports.resolve',
            'sanctions.view', 'sanctions.create', 'sanctions.revoke',
            'activity.moderation',
        ]);

        Role::findOrCreate('editor')->syncPermissions(['news.manage', 'teams.view', 'teams.edit', 'players.view', 'players.edit']);
    }
}
