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

        Permission::whereNotIn('name', $catalog)->get()->each->delete();

        Role::findOrCreate('super-admin');

        Role::findOrCreate('moderator')->syncPermissions([
            'reports.view', 'reports.resolve',
            'sanctions.view', 'sanctions.create', 'sanctions.revoke',
            'activity.moderation',
        ]);

        Role::findOrCreate('editor')->syncPermissions(['news.manage', 'teams.view', 'teams.edit', 'players.view', 'players.edit']);
    }
}
