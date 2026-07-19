<?php

/**
 * GC-Stats — Team: roster editing
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Services\RosterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RosterController extends Controller
{
    public function store(Request $request, Team $team, RosterService $rosterService): RedirectResponse
    {
        $validated = $request->validate([
            'player_id' => ['required', 'integer', 'exists:players,id'],
            'role' => ['nullable', 'string', Rule::in(RosterService::ROLES)],
            'joined_at' => ['required', 'date'],
        ]);

        $rosterService->addMember($team, $validated['player_id'], $validated['role'] ?? null, $validated['joined_at']);

        activity('team')->performedOn($team)->causedBy($request->user())
            ->withProperties(['team_id' => $team->id, 'player_id' => $validated['player_id']])->log('team.roster.member_added');

        return redirect()->route('teams.edit', [$team, $team->routeSlug()])->with('status', 'roster-member-added');
    }

    public function update(Request $request, Team $team, string $slug, int $entry, RosterService $rosterService): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['nullable', 'string', Rule::in(RosterService::ROLES)],
            'joined_at' => ['required', 'date'],
            'left_at' => ['nullable', 'date'],
        ]);

        $rosterService->updateEntry($team, $entry, $validated);

        activity('team')->performedOn($team)->causedBy($request->user())
            ->withProperties(['team_id' => $team->id, 'entry_id' => $entry])->log('team.roster.entry_updated');

        return back()->with('status', 'roster-entry-updated');
    }

    public function destroy(Request $request, Team $team, string $slug, int $entry, RosterService $rosterService): RedirectResponse
    {
        $rosterService->deleteEntry($team, $entry);

        activity('team')->performedOn($team)->causedBy($request->user())
            ->withProperties(['team_id' => $team->id, 'entry_id' => $entry])->log('team.roster.entry_removed');

        return back()->with('status', 'roster-entry-removed');
    }
}
