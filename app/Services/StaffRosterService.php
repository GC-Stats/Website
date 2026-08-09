<?php

/**
 * GC-Stats — Staff roster bulk-save (shared)
 *
 * The bulk upsert/delete-missing/cache-flush transaction shared by
 * App\Services\StaffOrganizationService (`staff_organizations`) and
 * App\Services\StaffTeamService (`staff_teams`) — identical logic, differing
 * only by table name, the pivot's other foreign key column, and which cache
 * tag prefix gets flushed. Neither subclass ever closes out a sibling active
 * (left_at null) row — see their own docblocks for why that's deliberate,
 * unlike App\Services\RosterService.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

abstract class StaffRosterService
{
    abstract protected function table(): string;

    /** The pivot's other foreign key column — 'organization_id' or 'team_id'. */
    abstract protected function foreignKey(): string;

    /** Cache tag prefix for the foreign side — flushed as "{prefix}_{id}". */
    abstract protected function cacheTagPrefix(): string;

    protected function roles(): array
    {
        return static::ROLES;
    }

    protected function entriesFor(int $foreignId): array
    {
        return DB::table($this->table())->where($this->foreignKey(), $foreignId)
            ->get(['id', 'staff_id', $this->foreignKey(), 'role', 'joined_at', 'left_at'])
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /**
     * Bulk-save pivot rows for a given staff member or foreign-side record.
     *
     * Each entry in $entries may contain an `id` (existing pivot row to
     * update) or be a new row to insert. Any existing rows not present in
     * $entries (matched by id) are deleted.
     *
     * @param  array<int, array{id?: int, staff_id: int, role?: string, joined_at: string, left_at?: ?string}>  $entries
     */
    public function save(string $keyColumn, int $keyValue, array $entries): Collection
    {
        $table = $this->table();
        $foreignKey = $this->foreignKey();

        $affectedStaffIds = [];
        $affectedForeignIds = [];

        $result = DB::transaction(function () use ($table, $foreignKey, $keyColumn, $keyValue, $entries, &$affectedStaffIds, &$affectedForeignIds) {
            $existingIds = DB::table($table)->where($keyColumn, $keyValue)->pluck('id')->toArray();
            $keptIds = [];

            $rows = collect();

            foreach ($entries as $entry) {
                $data = [
                    'staff_id' => $entry['staff_id'],
                    $foreignKey => $entry[$foreignKey],
                    'role' => $entry['role'] ?? $this->roles()[0],
                    'joined_at' => $entry['joined_at'],
                    'left_at' => $entry['left_at'] ?? null,
                    'updated_at' => now(),
                ];

                if (! empty($entry['id'])) {
                    DB::table($table)->where('id', $entry['id'])->update($data);
                    $id = $entry['id'];
                } else {
                    $data['created_at'] = now();
                    $id = DB::table($table)->insertGetId($data);
                }
                $keptIds[] = $id;

                $affectedStaffIds[] = $data['staff_id'];
                $affectedForeignIds[] = $data[$foreignKey];

                $rows->push(array_merge(['id' => $id], $data));
            }

            $toDelete = array_diff($existingIds, $keptIds);
            if (! empty($toDelete)) {
                $deletedRows = DB::table($table)->whereIn('id', $toDelete)->get(['staff_id', $foreignKey]);
                foreach ($deletedRows as $row) {
                    $affectedStaffIds[] = $row->staff_id;
                    $affectedForeignIds[] = $row->{$foreignKey};
                }

                DB::table($table)->whereIn('id', $toDelete)->delete();
            }

            return $rows;
        });

        foreach (array_unique($affectedStaffIds) as $staffId) {
            Cache::tags(["staff_{$staffId}"])->flush();
        }

        foreach (array_unique($affectedForeignIds) as $foreignId) {
            Cache::tags(["{$this->cacheTagPrefix()}_{$foreignId}"])->flush();
        }

        return $result;
    }
}
