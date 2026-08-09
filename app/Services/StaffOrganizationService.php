<?php

/**
 * GC-Stats — Staff/organization roster service
 *
 * Bulk-save logic for the `staff_organizations` pivot table, used by both
 * the organization's staff-roster panel and the staff member's own
 * organization-history panel so behaviour stays identical regardless of
 * which side initiates the edit. Deliberately parallel to
 * App\Services\RosterService rather than reusing it: unlike player_team,
 * a staff member is allowed several concurrent active (left_at null)
 * memberships across different organizations, so save() (see
 * StaffRosterService, shared with StaffTeamService) never closes out a
 * sibling active row.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use App\Models\Organization;
use App\Support\StaffRoles;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StaffOrganizationService extends StaffRosterService
{
    public const ROLES = StaffRoles::ORG_ROLES;

    protected function table(): string
    {
        return 'staff_organizations';
    }

    protected function foreignKey(): string
    {
        return 'organization_id';
    }

    protected function cacheTagPrefix(): string
    {
        return 'organization';
    }

    public function history(int $organizationId): Collection
    {
        return DB::table('staff_organizations')
            ->join('staff', 'staff.id', '=', 'staff_organizations.staff_id')
            ->where('staff_organizations.organization_id', $organizationId)
            ->select('staff_organizations.id', 'staff_organizations.staff_id', 'staff.handle as staff_handle', 'staff.pronouns as staff_pronouns', 'staff_organizations.role', 'staff_organizations.joined_at', 'staff_organizations.left_at')
            ->orderByDesc('staff_organizations.joined_at')
            ->get();
    }

    /**
     * The reverse of history() — every `staff_organizations` row for a given
     * staff member, for the admin staff page's editable organization-
     * history panel.
     */
    public function organizationHistory(int $staffId): Collection
    {
        return DB::table('staff_organizations')
            ->join('organization', 'organization.id', '=', 'staff_organizations.organization_id')
            ->where('staff_organizations.staff_id', $staffId)
            ->select('staff_organizations.id', 'staff_organizations.organization_id', 'organization.name as organization_name', 'staff_organizations.role', 'staff_organizations.joined_at', 'staff_organizations.left_at')
            ->orderByDesc('staff_organizations.joined_at')
            ->get();
    }

    public function addMember(Organization $organization, int $staffId, ?string $role, string $joinedAt): void
    {
        $entries = $this->entriesFor($organization->id);
        $entries[] = ['staff_id' => $staffId, 'organization_id' => $organization->id, 'role' => $role ?: self::ROLES[0], 'joined_at' => $joinedAt];

        $this->save('organization_id', $organization->id, $entries);
    }
}
