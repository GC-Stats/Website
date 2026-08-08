<?php

/**
 * GC-Stats — Staff experience role validation
 *
 * Shared by every controller that syncs staff_assignments entries
 * (Admin\TournamentController, Admin\MatchController, Admin\StaffController):
 * a role is only valid against the list matching whichever of team_id/
 * organization_id the same entry set — StaffRoles::TEAM_ROLES when team_id
 * is present, ::ORG_ROLES otherwise. Laravel's array validation can't
 * express "valid values depend on a sibling field" declaratively, so this
 * builds a per-entry closure rule that reads the sibling value straight off
 * the request by index.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Concerns;

use App\Support\StaffRoles;
use Closure;
use Illuminate\Http\Request;

trait ValidatesStaffExperienceRoles
{
    private function roleMatchesRepresentedEntity(Request $request): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($request) {
            preg_match('/^entries\.(\d+)\.role$/', $attribute, $matches);
            $index = $matches[1] ?? null;
            $teamId = $index !== null ? $request->input("entries.{$index}.team_id") : null;

            $validRoles = $teamId ? StaffRoles::TEAM_ROLES : StaffRoles::ORG_ROLES;

            if (! in_array($value, $validRoles, true)) {
                $fail(__('validation.in', ['attribute' => 'role']));
            }
        };
    }
}
