<?php

/**
 * GC-Stats — PlayerRosterApplier
 *
 * FieldApplier for a Player's `roster` field: moves the player onto a new
 * team through RosterService::addMember(), which is what actually closes
 * out any prior active player_team row — see the roster editing rule this
 * mirrors (adding a player to a team must close out her prior team's row).
 *
 * new_value shape: {"team_id": int, "role": ?string, "joined_at": "Y-m-d"}.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services\ChangeRequests\Appliers;

use App\Models\ChangeRequestItem;
use App\Models\Player;
use App\Models\Team;
use App\Services\ChangeRequests\FieldApplier;
use App\Services\RosterService;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class PlayerRosterApplier implements FieldApplier
{
    public function __construct(private readonly RosterService $roster) {}

    public function apply(Model $subject, mixed $newValue, ChangeRequestItem $item): void
    {
        if (! $subject instanceof Player) {
            throw new InvalidArgumentException('PlayerRosterApplier requires a Player subject.');
        }

        if (! isset($newValue['team_id'], $newValue['joined_at'])) {
            throw new InvalidArgumentException('roster new_value must include team_id and joined_at.');
        }

        $team = Team::findOrFail($newValue['team_id']);

        $this->roster->addMember($team, $subject->id, $newValue['role'] ?? null, $newValue['joined_at']);
    }
}
