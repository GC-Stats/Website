<?php

/**
 * GC-Stats — TeamNameApplier
 *
 * FieldApplier for a Team's `name` field. Unlike a plain SimpleAttributeApplier,
 * this goes through TeamProfileService::renameTeam() so a moderator-approved
 * rename is recorded in the team's name history the same way an admin
 * editing the profile form directly would be — see TeamProfileService's
 * recordNameChange().
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
use App\Services\TeamProfileService;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class TeamNameApplier implements FieldApplier
{
    public function __construct(private readonly TeamProfileService $teamProfiles) {}

    public function apply(Model $subject, mixed $newValue, ChangeRequestItem $item): void
    {
        if (! $subject instanceof Team) {
            throw new InvalidArgumentException('TeamNameApplier requires a Team subject.');
        }

        $this->teamProfiles->renameTeam($subject, (string) $newValue);
    }
}
