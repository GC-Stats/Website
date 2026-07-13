<?php

/**
 * GC-Stats — Tournament API controller
 *
 * Internal API for the Dashboard to list, create, edit and delete
 * tournaments, their phases and their participating teams.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Models\TournamentPhase;
use App\Models\TournamentTeam;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApiTournamentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $inputs = $request->only(['q', 'region', 'category', 'status', 'year', 'sort', 'direction', 'per_page']);

        $query = Tournament::query()->withCount('teams');

        if (! empty($inputs['q'])) {
            $query->where('name', 'like', '%'.$inputs['q'].'%');
        }
        if (! empty($inputs['region'])) {
            $query->where('region', $inputs['region']);
        }
        if (! empty($inputs['category'])) {
            $query->where('category', $inputs['category']);
        }
        if (! empty($inputs['status'])) {
            $query->where('status', $inputs['status']);
        }
        if (! empty($inputs['year'])) {
            $query->whereYear('start_date', $inputs['year']);
        }

        $sort = $inputs['sort'] ?? 'start_date';
        $direction = in_array(strtolower($inputs['direction'] ?? ''), ['asc', 'desc'])
            ? $inputs['direction']
            : ($sort === 'name' ? 'asc' : 'desc');

        $sortColumn = in_array($sort, ['name', 'start_date', 'end_date', 'status', 'region', 'category']) ? $sort : 'start_date';

        $query->orderBy($sortColumn, $direction);

        $perPage = min((int) ($inputs['per_page'] ?? 15), 100);

        return response()->json($query->paginate($perPage)->withQueryString());
    }

    public function meta(): JsonResponse
    {
        return response()->json([
            'regions' => Tournament::query()->distinct()->whereNotNull('region')->orderBy('region')->pluck('region'),
            'categories' => Tournament::query()->distinct()->whereNotNull('category')->orderBy('category')->pluck('category'),
            'statuses' => Tournament::query()->distinct()->whereNotNull('status')->orderBy('status')->pluck('status'),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $tournament = Tournament::with(['rootPhases.children', 'rootPhases.matches', 'teams'])->findOrFail($id);

        return response()->json([
            'tournament' => $tournament,
            'phases' => $tournament->rootPhases,
            'teams' => $tournament->teams,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateTournament($request);

        $tournament = DB::transaction(function () use ($validated) {
            $tournament = Tournament::create($this->coreColumns($validated));

            if (! empty($validated['phases'])) {
                $this->syncPhases($tournament, $validated['phases']);
            }

            return $tournament;
        });

        return response()->json([
            'success' => true,
            'tournament' => $tournament->fresh(['rootPhases.children', 'rootPhases.matches']),
        ], 201);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $tournament = Tournament::findOrFail($id);

        $validated = $this->validateTournament($request, true);

        DB::transaction(function () use ($tournament, $validated) {
            $tournament->update($this->coreColumns($validated, true));

            if (isset($validated['phases'])) {
                $this->syncPhases($tournament, $validated['phases']);
            }
        });

        return response()->json([
            'success' => true,
            'tournament' => $tournament->fresh(['rootPhases.children', 'rootPhases.matches']),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $tournament = Tournament::findOrFail($id);
        $tournament->delete();

        return response()->json(['success' => true]);
    }

    public function teams(int $id, Request $request): JsonResponse
    {
        Tournament::findOrFail($id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        $query = TournamentTeam::query()
            ->where('tournament_id', $id)
            ->with('team');

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->whereHas('team', fn ($t) => $t->where('name', 'like', "%{$q}%"));
        }

        return response()->json($query->paginate($perPage)->withQueryString());
    }

    public function attachTeam(int $id, Request $request): JsonResponse
    {
        $tournament = Tournament::findOrFail($id);

        $validated = $request->validate([
            'team_id' => ['required', 'integer', 'exists:teams,id'],
        ]);

        $alreadyRegistered = TournamentTeam::where('tournament_id', $tournament->id)
            ->where('team_id', $validated['team_id'])
            ->exists();

        if ($alreadyRegistered) {
            return response()->json([
                'error' => 'This team is already registered in this tournament.',
            ], 409);
        }

        $pivot = TournamentTeam::create([
            'tournament_id' => $tournament->id,
            'team_id' => $validated['team_id'],
        ]);

        $tournament->touch();

        return response()->json([
            'success' => true,
            'tournament_team' => $pivot->fresh('team'),
        ], 201);
    }

    public function detachTeam(int $id, int $teamId): JsonResponse
    {
        $tournament = Tournament::findOrFail($id);

        TournamentTeam::where('tournament_id', $id)->where('team_id', $teamId)->delete();

        $tournament->touch();

        return response()->json(['success' => true]);
    }

    public function preview(int $id): JsonResponse
    {
        Tournament::findOrFail($id);

        $code = Str::random(12);

        Cache::put("tournament_preview_{$code}", $id, now()->addHour());

        return response()->json([
            'url' => route('tournament.preview', ['code' => $code]),
            'code' => $code,
            'expires_at' => now()->addHour()->toIso8601String(),
        ]);
    }

    private function validateTournament(Request $request, bool $isUpdate = false): array
    {
        $rule = $isUpdate ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$rule, 'string', 'max:255'],
            'region' => [$rule, 'string', 'max:50'],
            'category' => [$rule, 'string', 'max:50'],
            'start_date' => [$rule, 'date'],
            'end_date' => [$rule, 'date'],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'prize_pool' => ['sometimes', 'nullable', 'string', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string'],
            'liquipedia_link' => ['sometimes', 'nullable', 'url', 'max:255'],
            'status' => ['sometimes', 'string', 'in:upcoming,live,finished'],
            'active' => ['sometimes', 'boolean'],
            'phases' => ['sometimes', 'array'],
            'phases.*.id' => ['sometimes', 'nullable', 'integer', 'exists:tournament_phases,id'],
            'phases.*.name' => ['required_with:phases', 'string', 'max:255'],
            'phases.*.format' => ['sometimes', 'nullable', 'string', 'max:100'],
            'phases.*.parent_id' => ['sometimes', 'nullable', 'integer'],
            'phases.*.order' => ['sometimes', 'integer'],
        ]);
    }

    private function coreColumns(array $validated, bool $isUpdate = false): array
    {
        $columns = ['name', 'region', 'category', 'start_date', 'end_date', 'location', 'prize_pool', 'description', 'liquipedia_link', 'status', 'active'];

        $data = array_intersect_key($validated, array_flip($columns));

        return $data;
    }

    private function syncPhases(Tournament $tournament, array $phases): void
    {
        $keptIds = [];

        // Map of temporary "index" -> real id, so children can reference parents created in this same request.
        $idMap = [];

        // First pass: create/update phases without resolving parent_id yet (in case parent appears later in the array).
        foreach ($phases as $index => $phase) {
            if (! empty($phase['id'])) {
                $model = TournamentPhase::where('tournament_id', $tournament->id)->find($phase['id']);
                if ($model) {
                    $model->update([
                        'name' => $phase['name'],
                        'format' => $phase['format'] ?? null,
                        'order' => $phase['order'] ?? 1,
                    ]);
                    $idMap[$index] = $model->id;
                    $keptIds[] = $model->id;

                    continue;
                }
            }

            $model = TournamentPhase::create([
                'tournament_id' => $tournament->id,
                'name' => $phase['name'],
                'format' => $phase['format'] ?? null,
                'order' => $phase['order'] ?? 1,
                'parent_id' => null,
            ]);

            $idMap[$index] = $model->id;
            $keptIds[] = $model->id;
        }

        // Second pass: resolve parent_id (may reference either an existing id or another entry's index).
        foreach ($phases as $index => $phase) {
            if (! array_key_exists('parent_id', $phase) || $phase['parent_id'] === null) {
                continue;
            }

            $parentId = $phase['parent_id'];

            if (isset($idMap[$parentId])) {
                $parentId = $idMap[$parentId];
            }

            TournamentPhase::where('id', $idMap[$index])->update(['parent_id' => $parentId]);
        }

        TournamentPhase::where('tournament_id', $tournament->id)
            ->whereNotIn('id', $keptIds)
            ->delete();
    }
}
