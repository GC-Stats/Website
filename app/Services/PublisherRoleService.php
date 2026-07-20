<?php

/**
 * GC-Stats — Publisher role service
 *
 * Lazily provisions the per-publisher roles (publisher_owner,
 * publisher_editor) the first time a publisher actually needs one, with a
 * starting set of permissions capped to the publisher's own max_permissions
 * ceiling (set by a site admin, see Admin\NewsPublisherController) — empty/
 * unset means the publisher starts with no self-management access at all.
 * publisher_editor defaults to news/media viewing and editing but not
 * publish/delete — fully editable afterward via News\RoleController,
 * independently per publisher.
 *
 * Every role/permission here lives on the 'publisher' guard (see
 * App\Support\PublisherPermissions) — distinct from Team roles' 'web'
 * guard — so a Team and a NewsPublisher sharing the same numeric id (both
 * scoped through App\Support\PermissionTeam::use($id)) can never cross-grant
 * a permission.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use App\Models\NewsPublisher;
use App\Models\User;
use App\Support\PermissionTeam;
use App\Support\PublisherPermissions;
use Spatie\Permission\Models\Role;

class PublisherRoleService
{
    public const ROLE_OWNER = 'publisher_owner';

    public const ROLE_EDITOR = 'publisher_editor';

    private const ROLE_COUNT = 2;

    public function ensureRolesExist(NewsPublisher $publisher): void
    {
        if (Role::where('team_id', $publisher->id)->where('guard_name', PublisherPermissions::GUARD)->count() >= self::ROLE_COUNT) {
            return;
        }

        PermissionTeam::use($publisher->id);

        $ceiling = $publisher->maxPermissions();

        $defaults = [
            self::ROLE_OWNER => $ceiling, // starts at the publisher's full ceiling; site admins can restrict per role from there
            self::ROLE_EDITOR => array_intersect(
                ['publisher.news.view', 'publisher.news.edit', 'publisher.media.view', 'publisher.media.upload'],
                $ceiling
            ),
        ];

        foreach ($defaults as $role => $permissions) {
            if (Role::where('name', $role)->where('team_id', $publisher->id)->where('guard_name', PublisherPermissions::GUARD)->exists()) {
                continue;
            }

            Role::create(['name' => $role, 'guard_name' => PublisherPermissions::GUARD])
                ->syncPermissions(array_values($permissions));
        }
    }

    public function assign(User $user, NewsPublisher $publisher, string $role): void
    {
        $this->ensureRolesExist($publisher);

        PermissionTeam::use($publisher->id);
        $user->assignRole(Role::findByName($role, PublisherPermissions::GUARD));
        PermissionTeam::global();
    }

    public function revoke(User $user, NewsPublisher $publisher, string $role): void
    {
        PermissionTeam::use($publisher->id);
        $user->removeRole(Role::findByName($role, PublisherPermissions::GUARD));
        PermissionTeam::global();
    }
}
