<?php

/**
 * GC-Stats — Role seeder
 *
 * Seeds the global (site-wide) roles. Team-scoped roles (team_owner,
 * team_manager, team_editor) are created lazily per team by
 * TeamRoleService::ensureRolesExist() instead of being seeded up front —
 * with tens of thousands of teams already imported, most will never have a
 * claimed manager, so pre-creating three role rows for each would be dead
 * weight.
 */

namespace Database\Seeders;

use App\Support\PermissionTeam;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        PermissionTeam::global();

        $permissions = [
            'site.moderate',
            'sanction.issue',
            'sanction.revoke',
            'news.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $superAdmin = Role::findOrCreate('super-admin');
        $superAdmin->givePermissionTo(Permission::all());

        Role::findOrCreate('moderator')->givePermissionTo(['site.moderate', 'sanction.issue', 'sanction.revoke']);
        Role::findOrCreate('editor')->givePermissionTo(['news.manage']);
    }
}
