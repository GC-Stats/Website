<?php

/**
 * GC-Stats — Staff experience (XP) service
 *
 * Bulk upsert-and-delete-missing save for `staff_assignments`, mirroring
 * StaffTeamService/StaffOrganizationService::save() — one PUT submits every
 * XP entry for a given scope in one transaction, entries missing from the
 * submission are deleted, entries carrying an `id` are updated in place.
 *
 * Unlike those two siblings, $scope isn't a single [column => value] pair:
 * the event-scoped admin editor (Tournament/Match show pages) keys on two
 * columns at once (assignable_type + assignable_id, polymorphic), while the
 * staff-scoped editor (a staff member's own admin page) keys on staff_id
 * alone and lets each entry carry its own assignable_type/assignable_id
 * instead. $scope's keys are merged onto every entry before it's written,
 * so whichever fields the caller fixes at the scope level don't need
 * repeating per row.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use App\Models\StaffAssignment;
use App\Support\StaffRoleMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StaffAssignmentService
{
    /**
     * @return Collection<int, StaffAssignment>
     */
    public function forAssignable(Model $assignable): Collection
    {
        /** @var MorphMany $relation */
        $relation = $assignable->staffAssignments();

        return $relation->with(['staff', 'team', 'organization'])->orderByDesc('id')->get();
    }

    /**
     * @return Collection<int, StaffAssignment>
     */
    public function forStaff(int $staffId): Collection
    {
        return StaffAssignment::where('staff_id', $staffId)
            ->whereIn('assignable_type', StaffAssignment::ASSIGNABLE_TYPES)
            // A match-type row's assignable needs its own tournament (for
            // tournamentStartDate()); a tournament-type row's assignable has
            // no such relation — morphWith() applies the eager load only to
            // the Matchs branch of the polymorphic result set.
            ->with(['team', 'organization'])
            ->with(['assignable' => fn ($morphTo) => $morphTo->morphWith([\App\Models\Matchs::class => ['tournament', 'teamA', 'teamB']])])
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $scope  Columns fixed for every entry in this save (e.g. ['assignable_type' => 'tournament', 'assignable_id' => 12], or ['staff_id' => 5]).
     * @param  array<int, array{id?: int, staff_id?: ?int, assignable_type?: string, assignable_id?: int, team_id?: ?int, organization_id?: ?int, role: string, metadata?: array}>  $entries
     * @return Collection<int, array<string, mixed>>
     */
    public function save(array $scope, array $entries): Collection
    {
        $affectedStaffIds = [];
        $affectedOrganizationIds = [];

        $result = DB::transaction(function () use ($scope, $entries, &$affectedStaffIds, &$affectedOrganizationIds) {
            $existingQuery = StaffAssignment::query();
            foreach ($scope as $column => $value) {
                $existingQuery->where($column, $value);
            }
            $existingIds = $existingQuery->pluck('id')->toArray();
            $keptIds = [];

            $rows = collect();

            foreach ($entries as $entry) {
                // $scope's values win when an entry doesn't provide its own
                // (e.g. the org-dashboard editor fixes organization_id via
                // scope and never submits it per-row) — array_merge() alone
                // would let the hardcoded defaults below null out a scope
                // value for any key the entry omits, so every key falls back
                // through the entry, then the scope, before defaulting.
                $data = array_merge($scope, [
                    'staff_id' => $entry['staff_id'] ?? $scope['staff_id'] ?? null,
                    'assignable_type' => $entry['assignable_type'] ?? $scope['assignable_type'] ?? null,
                    'assignable_id' => $entry['assignable_id'] ?? $scope['assignable_id'] ?? null,
                    'team_id' => $entry['team_id'] ?? $scope['team_id'] ?? null,
                    'organization_id' => $entry['organization_id'] ?? $scope['organization_id'] ?? null,
                    'role' => $entry['role'],
                    // Metadata only survives for roles that actually define a
                    // schema for it — a stray client-side value for any other
                    // role is dropped rather than trusted (see class docblock).
                    'metadata' => StaffRoleMetadata::hasMetadata($entry['role']) ? ($entry['metadata'] ?? []) : [],
                ]);

                if (! empty($entry['id'])) {
                    $assignment = StaffAssignment::findOrFail($entry['id']);
                    $assignment->update($data);
                    $id = $assignment->id;
                } else {
                    $id = StaffAssignment::create($data)->id;
                }
                $keptIds[] = $id;

                if ($data['staff_id']) {
                    $affectedStaffIds[] = $data['staff_id'];
                }
                if ($data['organization_id']) {
                    $affectedOrganizationIds[] = $data['organization_id'];
                }

                $rows->push(array_merge(['id' => $id], $data));
            }

            $toDelete = array_diff($existingIds, $keptIds);
            if (! empty($toDelete)) {
                $deletedRows = StaffAssignment::whereIn('id', $toDelete)->get(['staff_id', 'organization_id']);
                foreach ($deletedRows as $row) {
                    if ($row->staff_id) {
                        $affectedStaffIds[] = $row->staff_id;
                    }
                    if ($row->organization_id) {
                        $affectedOrganizationIds[] = $row->organization_id;
                    }
                }

                StaffAssignment::whereIn('id', $toDelete)->delete();
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
