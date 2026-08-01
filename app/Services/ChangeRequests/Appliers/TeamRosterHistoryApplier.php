<?php

/**
 * GC-Stats — TeamRosterHistoryApplier
 *
 * FieldApplier for a Team's `roster_history` field: corrects role/
 * joined_at/left_at on one existing `player_team` row via
 * RosterService::updateEntryForTeam(), scoped to the team so a proposal can
 * never touch a different team's row by id. Unlike `roster_add`
 * (TeamRosterAddApplier, which adds a new player), this only edits an
 * already-existing entry — it never inserts, deletes, or closes out
 * sibling rows. Mirrors PlayerRosterHistoryApplier.
 *
 * new_value shape: {"row_id": int, "role"?: ?string, "joined_at"?: ?string, "left_at"?: ?string}.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services\ChangeRequests\Appliers;

use App\Models\ChangeRequestItem;
use App\Models\Team;
use App\Services\ChangeRequests\FieldApplier;
use App\Services\RosterService;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use RuntimeException;

class TeamRosterHistoryApplier implements FieldApplier
{
    public function __construct(private readonly RosterService $roster) {}

    public function apply(Model $subject, mixed $newValue, ChangeRequestItem $item): void
    {
        if (! $subject instanceof Team) {
            throw new InvalidArgumentException('TeamRosterHistoryApplier requires a Team subject.');
        }

        if (! isset($newValue['row_id'])) {
            throw new InvalidArgumentException('roster_history new_value must include row_id.');
        }

        $updated = $this->roster->updateEntryForTeam($subject, (int) $newValue['row_id'], [
            'role' => $newValue['role'] ?? null,
            'joined_at' => $newValue['joined_at'] ?? null,
            'left_at' => array_key_exists('left_at', $newValue) ? $newValue['left_at'] : null,
        ]);

        if (! $updated) {
            throw new RuntimeException('This roster history entry no longer exists.');
        }
    }
}
