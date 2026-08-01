<?php

/**
 * GC-Stats — TeamRosterAddApplier
 *
 * FieldApplier for a Team's `roster_add` field: adds a new player onto the
 * team through RosterService::addMember(), which is what actually closes
 * out that player's prior active player_team row on another team — see the
 * roster editing rule this mirrors (adding a player to a team must close
 * out her prior team's row). Mirrors PlayerRosterApplier, reversed: that
 * one moves a player onto a team from the player's own change-request page,
 * this one adds a player onto a team from the team's own change-request page.
 *
 * new_value shape: {"player_id": int, "role": ?string, "joined_at": "Y-m-d"}.
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

class TeamRosterAddApplier implements FieldApplier
{
    public function __construct(private readonly RosterService $roster) {}

    public function apply(Model $subject, mixed $newValue, ChangeRequestItem $item): void
    {
        if (! $subject instanceof Team) {
            throw new InvalidArgumentException('TeamRosterAddApplier requires a Team subject.');
        }

        if (! isset($newValue['player_id'], $newValue['joined_at'])) {
            throw new InvalidArgumentException('roster_add new_value must include player_id and joined_at.');
        }

        $this->roster->addMember($subject, (int) $newValue['player_id'], $newValue['role'] ?? null, $newValue['joined_at']);
    }
}
