<?php

/**
 * GC-Stats — Match API controller
 *
 * Internal API for the Dashboard to list, view, create, edit and delete
 * matches, and to manage a match's veto (map pick/ban) sequence.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameMap;
use App\Models\Matchs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiMatchController extends Controller
{
    public function index(int $tournamentId, Request $request): JsonResponse
    {
        $query = Matchs::query()
            ->where('tournament_id', $tournamentId)
            ->with(['teamA:id,name,short_name', 'teamB:id,name,short_name', 'tournamentPhase:id,name']);

        if ($request->filled('team')) {
            $team = $request->input('team');
            $query->where(fn ($q) => $q->where('team_a_id', $team)->orWhere('team_b_id', $team));
        }

        if ($request->filled('phase')) {
            $query->where('phase_id', $request->input('phase'));
        }

        if ($request->filled('round_name')) {
            $query->where('round_name', $request->input('round_name'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date')) {
            $query->whereDate('scheduled_at', $request->input('date'));
        }

        $sort = $request->input('sort', 'scheduled_at');
        $direction = in_array(strtolower($request->input('direction', 'desc')), ['asc', 'desc'])
            ? $request->input('direction', 'desc')
            : 'desc';

        $sortColumn = in_array($sort, ['scheduled_at', 'round_number', 'match_order', 'status', 'created_at']) ? $sort : 'scheduled_at';

        $query->orderBy($sortColumn, $direction);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return response()->json($query->paginate($perPage)->withQueryString());
    }

    public function store(int $tournamentId, Request $request): JsonResponse
    {
        $validated = $this->validateMatch($request);
        $validated['tournament_id'] = $tournamentId;

        $match = Matchs::create($validated);

        return response()->json([
            'success' => true,
            'match' => $match->fresh(['teamA', 'teamB', 'tournamentPhase']),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $match = Matchs::with([
            'teamA', 'teamB', 'tournamentPhase', 'game_maps', 'map_bans.team', 'map_bans.sidePickedBy',
        ])->findOrFail($id);

        return response()->json(['match' => $match]);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $match = Matchs::findOrFail($id);

        $validated = $this->validateMatch($request, true);

        $match->update($validated);

        return response()->json([
            'success' => true,
            'match' => $match->fresh(['teamA', 'teamB', 'tournamentPhase', 'game_maps']),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $match = Matchs::findOrFail($id);
        $match->delete();

        return response()->json(['success' => true]);
    }

    public function saveVeto(int $id, Request $request): JsonResponse
    {
        $match = Matchs::findOrFail($id);

        $validated = $request->validate([
            'vetos' => ['required', 'array'],
            'vetos.*.team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'vetos.*.map_name' => ['required', 'string', 'max:50'],
            'vetos.*.type' => ['required', 'string', 'in:pick,ban,decider'],
            'vetos.*.side' => ['nullable', 'string', 'max:20'],
            'vetos.*.side_picked_by' => ['nullable', 'integer', 'exists:teams,id'],
            'vetos.*.order' => ['required', 'integer'],
        ]);

        DB::transaction(function () use ($match, $validated) {
            $match->map_bans()->delete();

            foreach ($validated['vetos'] as $veto) {
                $match->map_bans()->create($veto);
            }

            $this->recomputeGameMaps($match, $validated['vetos']);
        });

        return response()->json([
            'success' => true,
            'match' => $match->fresh(['map_bans.team', 'map_bans.sidePickedBy', 'game_maps']),
        ]);
    }

    public function resetMaps(int $id): JsonResponse
    {
        $match = Matchs::findOrFail($id);

        DB::transaction(function () use ($match) {
            $match->game_maps()->delete();
            $match->map_bans()->delete();
        });

        return response()->json([
            'success' => true,
            'match' => $match->fresh(['map_bans', 'game_maps']),
        ]);
    }

    /**
     * Recreate the match's game_maps from the veto's picked/decider maps,
     * preserving any existing map's stats/score when the map name is unchanged.
     */
    private function recomputeGameMaps(Matchs $match, array $vetos): void
    {
        $playedMaps = collect($vetos)
            ->filter(fn ($v) => in_array($v['type'], ['pick', 'decider']))
            ->sortBy('order')
            ->values();

        $existingMaps = $match->game_maps()->orderBy('order')->get()->keyBy('map_name');
        $keptIds = [];

        foreach ($playedMaps as $index => $veto) {
            $order = $index + 1;
            $existing = $existingMaps->get($veto['map_name']);

            if ($existing) {
                $existing->update(['order' => $order]);
                $keptIds[] = $existing->id;
            } else {
                $map = GameMap::create([
                    'match_id' => $match->id,
                    'map_name' => $veto['map_name'],
                    'order' => $order,
                    'is_completed' => false,
                ]);
                $keptIds[] = $map->id;
            }
        }

        $match->game_maps()->whereNotIn('id', $keptIds)->delete();
    }

    private function validateMatch(Request $request, bool $isUpdate = false): array
    {
        $rule = $isUpdate ? 'sometimes' : 'required';

        return $request->validate([
            'phase_id' => [$rule, 'integer', 'exists:tournament_phases,id'],
            'team_a_id' => ['sometimes', 'nullable', 'integer', 'exists:teams,id'],
            'team_b_id' => ['sometimes', 'nullable', 'integer', 'exists:teams,id'],
            'scheduled_at' => [$rule, 'date'],
            'status' => ['sometimes', 'string', 'in:upcoming,live,finished'],
            'team_a_score' => ['sometimes', 'nullable', 'integer'],
            'team_b_score' => ['sometimes', 'nullable', 'integer'],
            'best_of' => ['sometimes', 'nullable', 'integer'],
            'patch' => ['sometimes', 'nullable', 'string', 'max:20'],
            'match_order' => ['sometimes', 'nullable', 'integer'],
            'round_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'round_number' => ['sometimes', 'nullable', 'integer'],
        ]);
    }
}
