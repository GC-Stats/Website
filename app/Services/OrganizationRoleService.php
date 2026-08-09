<?php

/**
 * GC-Stats — Organization role service
 *
 * Lazily provisions the per-organization roles (organization_owner,
 * organization_editor) the first time an organization actually needs one,
 * with a starting set of permissions capped to the organization's own
 * max_permissions ceiling (set by a site admin, see
 * Admin\OrganizationController) — empty/unset means the organization starts
 * with no self-management access at all. organization_editor defaults to
 * staff/news/media viewing and editing but not publish/delete — fully
 * editable afterward via an org-scoped role controller, independently per
 * organization.
 *
 * Every role/permission here lives on the 'organization' guard (see
 * App\Support\OrganizationPermissions) — distinct from Team roles' 'web'
 * guard — so a Team/Organization sharing the same numeric id (all scoped
 * through App\Support\PermissionTeam::use($id)) can never cross-grant a
 * permission.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use App\Support\OrganizationPermissions;
use App\Support\PermissionTeam;
use Spatie\Permission\Models\Role;

class OrganizationRoleService
{
    public const ROLE_OWNER = 'organization_owner';

    public const ROLE_EDITOR = 'organization_editor';

    private const ROLE_COUNT = 2;

    public function ensureRolesExist(Organization $organization): void
    {
        if (Role::where('team_id', $organization->id)->where('guard_name', OrganizationPermissions::GUARD)->count() >= self::ROLE_COUNT) {
            return;
        }

        PermissionTeam::use($organization->id);

        $ceiling = $organization->maxPermissions();

        $defaults = [
            self::ROLE_OWNER => $ceiling, // starts at the organization's full ceiling; site admins can restrict per role from there
            self::ROLE_EDITOR => array_intersect(
                [
                    'organization.staff.view', 'organization.staff.edit',
                    'organization.news.view', 'organization.news.edit', 'organization.media.view', 'organization.media.upload',
                    'organization.streams.view', 'organization.streams.edit', 'organization.vods.link',
                ],
                $ceiling
            ),
        ];

        foreach ($defaults as $role => $permissions) {
            if (Role::where('name', $role)->where('team_id', $organization->id)->where('guard_name', OrganizationPermissions::GUARD)->exists()) {
                continue;
            }

            Role::create(['name' => $role, 'guard_name' => OrganizationPermissions::GUARD])
                ->syncPermissions(array_values($permissions));
        }
    }

    public function assign(User $user, Organization $organization, string $role): void
    {
        $this->ensureRolesExist($organization);

        PermissionTeam::use($organization->id);
        $user->assignRole(Role::findByName($role, OrganizationPermissions::GUARD));
        PermissionTeam::global();
    }

    public function revoke(User $user, Organization $organization, string $role): void
    {
        PermissionTeam::use($organization->id);
        $user->removeRole(Role::findByName($role, OrganizationPermissions::GUARD));
        PermissionTeam::global();
    }
}
