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
use App\Support\PublisherPermissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        PermissionTeam::global();

        $catalog = [...AdminPermissions::all()];

        foreach ($catalog as $permission) {
            Permission::findOrCreate($permission);
        }

        Permission::where('guard_name', 'web')->whereNotIn('name', $catalog)->get()->each->delete();

        $publisherCatalog = PublisherPermissions::all();

        foreach ($publisherCatalog as $permission) {
            Permission::findOrCreate($permission, PublisherPermissions::GUARD);
        }

        Permission::where('guard_name', PublisherPermissions::GUARD)->whereNotIn('name', $publisherCatalog)->get()->each->delete();

        if (! Role::where('team_id', PermissionTeam::GLOBAL_ID)->where('is_super_admin', true)->exists()) {
            // Only reachable on a fresh install, or a DB seeded before the
            // is_super_admin flag existed — once flagged, the role is found
            // above regardless of subsequent renames.
            Role::findOrCreate('super-admin')->update(['is_super_admin' => true]);
        }
    }
}
