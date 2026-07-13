<?php

/**
 * GC-Stats — Player API controller
 *
 * Exposes a JSON endpoint returning a player's profile information
 * (handle, name, country, bio, photo, socials, current teams).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Services\RosterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiPlayerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Player::query();

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($query) use ($q) {
                $query->where('handle', 'like', "%{$q}%")
                    ->orWhere('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%");
            });
        }

        $perPage = min((int) $request->input('per_page', 15), 100);

        return response()->json(
            $query->orderBy('handle')
                ->paginate($perPage)
                ->withQueryString()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'handle' => ['required', 'string', 'max:50'],
            'first_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'country_code' => ['sometimes', 'nullable', 'string', 'size:2'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'socials' => ['sometimes', 'array'],
            'discord_id' => ['sometimes', 'nullable', 'string', 'max:20'],
            'vlr_id' => ['sometimes', 'nullable', 'integer', 'max:99999999'],
            'val_id' => ['sometimes', 'nullable', 'string', 'max:100'],
            'liquipedia_link' => ['sometimes', 'nullable', 'url', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $player = Player::create($validated);

        return response()->json([
            'success' => true,
            'player' => $player->fresh(),
        ], 201);
    }

    public function teamHistory(int $id): JsonResponse
    {
        $player = Player::findOrFail($id);

        $history = DB::table('player_team')
            ->join('teams', 'teams.id', '=', 'player_team.team_id')
            ->where('player_team.player_id', $id)
            ->select('player_team.id', 'player_team.team_id', 'teams.name as team_name', 'teams.short_name as team_short_name', 'player_team.role', 'player_team.joined_at', 'player_team.left_at')
            ->orderByDesc('player_team.joined_at')
            ->get();

        return response()->json([
            'player_id' => $player->id,
            'history' => $history,
        ]);
    }

    public function saveTeamHistory(int $id, Request $request, RosterService $rosterService): JsonResponse
    {
        $player = Player::findOrFail($id);

        $validated = $request->validate([
            'history' => ['required', 'array'],
            'history.*.id' => ['sometimes', 'nullable', 'integer', 'exists:player_team,id'],
            'history.*.team_id' => ['required', 'integer', 'exists:teams,id'],
            'history.*.role' => ['sometimes', 'nullable', 'string', 'max:50'],
            'history.*.joined_at' => ['required', 'date'],
            'history.*.left_at' => ['sometimes', 'nullable', 'date'],
        ]);

        $entries = array_map(fn ($entry) => array_merge($entry, ['player_id' => $player->id]), $validated['history']);

        $rows = $rosterService->save('player_id', $player->id, $entries);

        return response()->json([
            'success' => true,
            'history' => $rows,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $player = Player::with([
            'teams' => fn ($q) => $q->wherePivot('left_at', null)
                ->select('teams.id', 'teams.name', 'teams.short_name'),
        ])->findOrFail($id);

        return response()->json([
            'id' => $player->id,
            'handle' => $player->handle,
            'first_name' => $player->first_name,
            'last_name' => $player->last_name,
            'country_code' => $player->country_code,
            'bio' => $player->bio,
            'photo' => $player->profile_photo,
            'socials' => $player->socials,
            'vlr_id' => $player->vlr_id,
            'liquipedia_link' => $player->liquipedia_link,
            'discord_id' => $player->discord_id,
            'team' => $player->teams->first() ? [
                'id' => $player->teams->first()->id,
                'name' => $player->teams->first()->name,
                'short_name' => $player->teams->first()->short_name,
            ] : null,
        ]);
    }

    public function showByVlrId(string $vlrId): JsonResponse
    {
        $player = Player::with([
            'teams' => fn ($q) => $q->wherePivot('left_at', null)
                ->select('teams.id', 'teams.name', 'teams.short_name'),
        ])->where('vlr_id', $vlrId)->firstOrFail();

        return response()->json([
            'id' => $player->id,
            'handle' => $player->handle,
            'first_name' => $player->first_name,
            'last_name' => $player->last_name,
            'country_code' => $player->country_code,
            'bio' => $player->bio,
            'photo' => $player->profile_photo,
            'socials' => $player->socials,
            'vlr_id' => $player->vlr_id,
            'liquipedia_link' => $player->liquipedia_link,
            'discord_id' => $player->discord_id,
            'team' => $player->teams->first() ? [
                'id' => $player->teams->first()->id,
                'name' => $player->teams->first()->name,
                'short_name' => $player->teams->first()->short_name,
            ] : null,
        ]);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $player = Player::findOrFail($id);

        $validated = $request->validate([
            'handle' => ['sometimes', 'string', 'max:50'],
            'first_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'country_code' => ['sometimes', 'nullable', 'string', 'size:2'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'socials' => ['sometimes', 'array'],
            'discord_id' => ['sometimes', 'nullable', 'string', 'max:20'],
            'vlr_id' => ['sometimes', 'nullable', 'integer', 'max:99999999'],
            'liquipedia_link' => ['sometimes', 'nullable', 'url', 'max:255'],
        ]);

        $player->update($validated);

        return response()->json([
            'success' => true,
            'player' => $player->fresh(),
        ]);
    }

    public function resetValId(int $id): JsonResponse
    {
        $player = Player::findOrFail($id);

        $player->update(['val_id' => null]);

        return response()->json([
            'success' => true,
            'player' => $player->fresh(),
        ]);
    }

    public function resetDiscordId(int $id): JsonResponse
    {
        $player = Player::findOrFail($id);

        $player->update(['discord_id' => null]);

        return response()->json([
            'success' => true,
            'player' => $player->fresh(),
        ]);
    }

    public function updateTeam(int $id, Request $request): JsonResponse
    {
        $player = Player::with(['teams' => fn ($q) => $q->wherePivot('left_at', null)])->findOrFail($id);

        $validated = $request->validate([
            'current_team_id' => ['sometimes', 'nullable', 'exists:teams,id'],
            'leave_date' => ['sometimes', 'nullable', 'date'],
            'join_date' => ['sometimes', 'nullable', 'date'],
        ]);

        if ($player->teams->isNotEmpty()) {
            $player->teams()->wherePivot('left_at', null)->updateExistingPivot(
                $player->teams->first()->id,
                ['left_at' => $validated['leave_date'] ?? now()]
            );
        }

        if (! empty($validated['current_team_id'])) {
            $player->teams()->attach($validated['current_team_id'], [
                'joined_at' => $validated['join_date'] ?? now(),
                'left_at' => null,
            ]);
        }

        return response()->json(['success' => true]);
    }
}
