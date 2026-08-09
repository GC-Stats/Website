<?php

/**
 * GC-Stats — Organization access checks
 *
 * Shared "does this user get to do X on this organization" logic, used by
 * both Admin\OrganizationController (site-admin view, dual-access with the
 * organization's own roles) and Organization\DashboardController (the
 * organization's own dedicated, non-admin dashboard) — extracted so the two
 * surfaces can never drift apart on what counts as "can edit".
 *
 * A site editor with the matching AdminPermissions permission can always
 * act; otherwise the user needs the matching 'organization.*' permission
 * within this specific organization's own scope (see
 * App\Support\OrganizationPermissions), checked by switching the active
 * PermissionTeam context for the duration of the check.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use App\Support\OrganizationScope;
use App\Support\PermissionTeam;

class OrganizationAccessService
{
    /**
     * A site editor with organizations.view can see any organization;
     * otherwise the user must hold *some* role on this specific organization.
     */
    public function canView(User $user, Organization $organization): bool
    {
        return $user->can('organizations.view')
            || OrganizationScope::organizationIdsForUser($user->id)->contains($organization->id);
    }

    public function canEditProfile(User $user, Organization $organization): bool
    {
        return $this->hasOrganizationPermission($user, $organization, 'organizations.edit', 'organization.profile.edit');
    }

    public function canUploadLogo(User $user, Organization $organization): bool
    {
        return $this->hasOrganizationPermission($user, $organization, 'organizations.edit', 'organization.logo.upload');
    }

    public function canManageStaff(User $user, Organization $organization): bool
    {
        return $this->hasOrganizationPermission($user, $organization, 'organizations.edit', 'organization.staff.edit');
    }

    public function canManageExperience(User $user, Organization $organization): bool
    {
        return $this->hasOrganizationPermission($user, $organization, 'staff.assignments.manage', 'organization.staff.assignments.manage');
    }

    public function hasOrganizationPermission(User $user, Organization $organization, ?string $adminPermission, string $organizationPermission): bool
    {
        if ($adminPermission && $user->can($adminPermission)) {
            return true;
        }

        PermissionTeam::use($organization->id);
        $has = $user->can($organizationPermission);
        PermissionTeam::global();

        return $has;
    }
}
