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

    public function sync(Request $request, Team $team, string $slug, RosterService $rosterService): RedirectResponse
    {
        $validated = $request->validate([
            'entries' => ['array'],
            'entries.*.id' => ['nullable', 'integer', Rule::exists('player_team', 'id')->where('team_id', $team->id)],
            'entries.*.player_id' => ['required', 'integer', 'exists:players,id'],
            'entries.*.role' => ['nullable', 'string', Rule::in(RosterService::ROLES)],
            'entries.*.joined_at' => ['required', 'date'],
            'entries.*.left_at' => ['nullable', 'date'],
        ]);

        $entries = collect($validated['entries'] ?? [])
            ->map(fn (array $entry) => [...$entry, 'team_id' => $team->id])
            ->all();

        $rosterService->save('team_id', $team->id, $entries);

        activity('team')->performedOn($team)->causedBy($request->user())
            ->withProperties(['team_id' => $team->id])->log('team.roster.synced');

        return back()->with('status', 'roster-synced');
    }
}
