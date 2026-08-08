<?php

/**
 * GC-Stats — Staff/organization roster service
 *
 * Shared bulk-save logic for the `staff_organizations` pivot table, used by
 * both the organization's staff-roster panel and the staff member's own
 * organization-history panel so behaviour stays identical regardless of
 * which side initiates the edit. Deliberately parallel to
 * App\Services\RosterService rather than reusing it: unlike player_team,
 * a staff member is allowed several concurrent active (left_at null)
 * memberships across different organizations, so save() here never closes
 * out a sibling active row.
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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StaffOrganizationService
{
    public const ROLES = StaffRoles::ORG_ROLES;

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

    private function entriesFor(int $organizationId): array
    {
        return DB::table('staff_organizations')->where('organization_id', $organizationId)
            ->get(['id', 'staff_id', 'organization_id', 'role', 'joined_at', 'left_at'])
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /**
     * Bulk-save `staff_organizations` rows for a given staff member or
     * organization.
     *
     * Each entry in $entries may contain an `id` (existing pivot row to
     * update) or be a new row to insert. Any existing rows not present in
     * $entries (matched by id) are deleted. Unlike RosterService::save(),
     * no sibling active row is ever closed out — a staff member can be
     * active on several organizations at once.
     *
     * @param  array<int, array{id?: int, staff_id: int, organization_id: int, role?: string, joined_at: string, left_at?: ?string}>  $entries
     */
    public function save(string $keyColumn, int $keyValue, array $entries): Collection
    {
        $affectedStaffIds = [];
        $affectedOrganizationIds = [];

        $result = DB::transaction(function () use ($keyColumn, $keyValue, $entries, &$affectedStaffIds, &$affectedOrganizationIds) {
            $existingIds = DB::table('staff_organizations')->where($keyColumn, $keyValue)->pluck('id')->toArray();
            $keptIds = [];

            $rows = collect();

            foreach ($entries as $entry) {
                $data = [
                    'staff_id' => $entry['staff_id'],
                    'organization_id' => $entry['organization_id'],
                    'role' => $entry['role'] ?? self::ROLES[0],
                    'joined_at' => $entry['joined_at'],
                    'left_at' => $entry['left_at'] ?? null,
                    'updated_at' => now(),
                ];

                if (! empty($entry['id'])) {
                    DB::table('staff_organizations')->where('id', $entry['id'])->update($data);
                    $id = $entry['id'];
                } else {
                    $data['created_at'] = now();
                    $id = DB::table('staff_organizations')->insertGetId($data);
                }
                $keptIds[] = $id;

                $affectedStaffIds[] = $data['staff_id'];
                $affectedOrganizationIds[] = $data['organization_id'];

                $rows->push(array_merge(['id' => $id], $data));
            }

            $toDelete = array_diff($existingIds, $keptIds);
            if (! empty($toDelete)) {
                $deletedRows = DB::table('staff_organizations')->whereIn('id', $toDelete)->get(['staff_id', 'organization_id']);
                foreach ($deletedRows as $row) {
                    $affectedStaffIds[] = $row->staff_id;
                    $affectedOrganizationIds[] = $row->organization_id;
                }

                DB::table('staff_organizations')->whereIn('id', $toDelete)->delete();
            }

            return $rows;
        });

        foreach (array_unique($affectedStaffIds) as $staffId) {
            Cache::tags(["staff_{$staffId}"])->flush();
        }

        foreach (array_unique($affectedOrganizationIds) as $organizationId) {
            Cache::tags(["organization_{$organizationId}"])->flush();
        }

        return $result;
    }
}
