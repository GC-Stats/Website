<?php

/**
 * GC-Stats — Team API controller
 *
 * Exposes a JSON endpoint returning a team's profile information
 * (name, country, region, bio, logo, socials, current roster).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Services\RosterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiTeamController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Team::query();

        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->input('q').'%');
        }

        $perPage = min((int) $request->input('per_page', 15), 100);

        return response()->json(
            $query->orderBy('name')
                ->paginate($perPage)
                ->withQueryString()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'short_name' => ['sometimes', 'nullable', 'string', 'max:20'],
            'country_code' => ['sometimes', 'nullable', 'string', 'size:2'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'socials' => ['sometimes', 'array'],
            'vlr_id' => ['sometimes', 'nullable', 'string', 'max:50'],
            'liquipedia_link' => ['sometimes', 'nullable', 'url', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $team = Team::create($validated);

        return response()->json([
            'success' => true,
            'team' => $team->fresh(),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $team = Team::with([
            'currentPlayers:id,handle,first_name,last_name,country_code',
        ])->findOrFail($id);

        return response()->json([
            'id' => $team->id,
            'name' => $team->name,
            'short_name' => $team->short_name,
            'country_code' => $team->country_code,
            'region' => $team->region,
            'bio' => $team->bio,
            'logo' => $team->logo,
            'vlr_id' => $team->vlr_id,
            'liquipedia_link' => $team->liquipedia_link,
            'socials' => $team->socials,
            'roster_ids' => $team->currentPlayers->pluck('id'),
            'players' => $team->currentPlayers->map(fn ($p) => [
                'id' => $p->id,
                'handle' => $p->handle,
                'first_name' => $p->first_name,
                'last_name' => $p->last_name,
                'country_code' => $p->country_code,
            ]),
        ]);
    }

    public function showByVlrId(string $vlrId): JsonResponse
    {
        $team = Team::with([
            'currentPlayers:id,handle,first_name,last_name,country_code',
        ])->where('vlr_id', $vlrId)->firstOrFail();

        return response()->json([
            'id' => $team->id,
            'name' => $team->name,
            'short_name' => $team->short_name,
            'country_code' => $team->country_code,
            'region' => $team->region,
            'bio' => $team->bio,
            'logo' => $team->logo,
            'vlr_id' => $team->vlr_id,
            'liquipedia_link' => $team->liquipedia_link,
            'socials' => $team->socials,
            'roster_ids' => $team->currentPlayers->pluck('id'),
            'players' => $team->currentPlayers->map(fn ($p) => [
                'id' => $p->id,
                'handle' => $p->handle,
                'first_name' => $p->first_name,
                'last_name' => $p->last_name,
                'country_code' => $p->country_code,
            ]),
        ]);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $team = Team::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'short_name' => ['sometimes', 'nullable', 'string', 'max:20'],
            'country_code' => ['sometimes', 'nullable', 'string', 'size:2'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'vlr_id' => ['sometimes', 'nullable', 'integer', 'max:99999999'],
            'liquipedia_link' => ['sometimes', 'nullable', 'url', 'max:255'],
            'socials' => ['sometimes', 'array'],
        ]);

        $team->update($validated);

        return response()->json([
            'success' => true,
            'team' => $team->fresh(),
        ]);
    }

    public function roster(int $id): JsonResponse
    {
        $team = Team::with([
            'currentPlayers:id,handle,first_name,last_name,country_code',
        ])->findOrFail($id);

        return response()->json([
            'team_id' => $team->id,
            'roster_ids' => $team->currentPlayers->pluck('id'),
            'players' => $team->currentPlayers->map(fn ($p) => [
                'id' => $p->id,
                'handle' => $p->handle,
                'first_name' => $p->first_name,
                'last_name' => $p->last_name,
                'country_code' => $p->country_code,
                'role' => $p->pivot->role,
                'joined_at' => $p->pivot->joined_at,
            ]),
        ]);
    }

    public function rosterHistory(int $id): JsonResponse
    {
        $team = Team::findOrFail($id);

        $history = DB::table('player_team')
            ->join('players', 'players.id', '=', 'player_team.player_id')
            ->where('player_team.team_id', $id)
            ->select('player_team.id', 'player_team.player_id', 'players.handle as player_handle', 'player_team.role', 'player_team.joined_at', 'player_team.left_at')
            ->orderByDesc('player_team.joined_at')
            ->get();

        return response()->json([
            'team_id' => $team->id,
            'history' => $history,
        ]);
    }

    public function saveRoster(int $id, Request $request, RosterService $rosterService): JsonResponse
    {
        $team = Team::findOrFail($id);

        $validated = $request->validate([
            'roster' => ['required', 'array'],
            'roster.*.id' => ['sometimes', 'nullable', 'integer', 'exists:player_team,id'],
            'roster.*.player_id' => ['required', 'integer', 'exists:players,id'],
            'roster.*.role' => ['sometimes', 'nullable', 'string', 'max:50'],
            'roster.*.joined_at' => ['required', 'date'],
            'roster.*.left_at' => ['sometimes', 'nullable', 'date'],
        ]);

        $entries = array_map(fn ($entry) => array_merge($entry, ['team_id' => $team->id]), $validated['roster']);

        $rows = $rosterService->save('team_id', $team->id, $entries);

        return response()->json([
            'success' => true,
            'roster' => $rows,
        ]);
    }

    public function deleteRosterEntry(int $id, int $entry, RosterService $rosterService): JsonResponse
    {
        Team::findOrFail($id);

        $deleted = $rosterService->deleteEntry($entry);

        if (! $deleted) {
            return response()->json(['error' => 'Roster entry not found'], 404);
        }

        return response()->json(['success' => true]);
    }
}
