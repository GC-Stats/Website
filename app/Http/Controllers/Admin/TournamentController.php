<?php

/**
 * GC-Stats — Admin: tournaments
 *
 * CRUD over tournaments and their phases/participating teams. Editing a
 * finished tournament requires the extra `tournaments.finished.edit`
 * permission on top of `tournaments.edit` — see the `update()` guard.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentPhase;
use App\Models\TournamentTeam;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TournamentController extends Controller
{
    public const CATEGORIES = ['Championship', 'Regional', 'Open Qualifier', 'Cash Cups', 'Showmatch', 'Unofficial tournament'];

    public function index(Request $request): View
    {
        $search = $request->get('q');
        $region = $request->get('region');
        $category = $request->get('category');
        $status = $request->get('status');
        $active = $request->get('active');
        $sort = $request->get('sort', 'start_date');

        $tournaments = Tournament::query()
            ->withCount('teams')
            ->when($search, fn ($query) => $query->where('name', 'like', '%'.$this->escapeLike($search).'%'))
            ->when($region, fn ($query) => $query->where('region', $region))
            ->when($category === '__custom__', fn ($query) => $query->whereNotIn('category', self::CATEGORIES))
            ->when($category && $category !== '__custom__', fn ($query) => $query->where('category', $category))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($active !== null && $active !== '', fn ($query) => $query->where('active', $active === '1'))
            ->when($sort === 'name', fn ($query) => $query->orderBy('name'))
            ->when($sort === 'start_date', fn ($query) => $query->orderByDesc('start_date'))
            ->paginate(25)
            ->withQueryString();

        return view('admin.tournaments.index', [
            'tournaments' => $tournaments,
            'search' => $search ?? '',
            'region' => $region ?? '',
            'category' => $category ?? '',
            'status' => $status ?? '',
            'active' => $active ?? '',
            'sort' => $sort,
            'regions' => array_keys(config('regions.riot_api')),
            'categories' => self::CATEGORIES,
        ]);
    }

    public function create(): View
    {
        return view('admin.tournaments.create', [
            'regions' => array_keys(config('regions.riot_api')),
            'categories' => self::CATEGORIES,
        ]);
    }

    public function show(Tournament $tournament): View
    {
        $tournament->load(['rootPhases.children']);

        $teams = $tournament->teams()->orderBy('name')->paginate(15, ['*'], 'teams_page')->withQueryString();

        return view('admin.tournaments.show', [
            'tournament' => $tournament,
            'teams' => $teams,
            'search' => request()->get('q', ''),
            'searchResults' => request()->filled('q')
                ? Team::where('name', 'like', '%'.$this->escapeLike(request()->get('q')).'%')
                    ->whereNotIn('id', $tournament->teams()->pluck('teams.id'))
                    ->limit(10)->get()
                : collect(),
        ]);
    }

    public function edit(Tournament $tournament): View
    {
        $tournament->load('rootPhases.children');

        return view('admin.tournaments.edit', [
            'tournament' => $tournament,
            'regions' => array_keys(config('regions.riot_api')),
            'categories' => self::CATEGORIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->resolveCustomCategory($this->validateTournament($request));

        $tournament = DB::transaction(function () use ($validated) {
            $tournament = Tournament::create($this->coreColumns($validated));

            if (! empty($validated['phases'])) {
                $this->syncPhases($tournament, $validated['phases']);
            }

            return $tournament;
        });

        activity('tournament')->causedBy($request->user())
            ->performedOn($tournament)->log('tournament.created');

        return redirect()->route('admin.tournaments.show', $tournament)->with('status', 'tournament-created');
    }

    public function update(Request $request, Tournament $tournament): RedirectResponse
    {
        abort_unless(
            $tournament->status !== 'finished' || $request->user()->can('tournaments.finished.edit'),
            403,
            'Only a user with tournaments.finished.edit can edit a finished tournament.'
        );

        $validated = $this->resolveCustomCategory($this->validateTournament($request, true));

        DB::transaction(function () use ($tournament, $validated) {
            $tournament->update($this->coreColumns($validated, true));

            if (isset($validated['phases'])) {
                $this->syncPhases($tournament, $validated['phases']);
            }
        });

        activity('tournament')->causedBy($request->user())
            ->performedOn($tournament)->log('tournament.updated');

        return redirect()->route('admin.tournaments.show', $tournament)->with('status', 'tournament-updated');
    }

    public function destroy(Request $request, Tournament $tournament): RedirectResponse
    {
        $tournament->delete();

        activity('tournament')->causedBy($request->user())->log('tournament.deleted');

        return redirect()->route('admin.tournaments.index')->with('status', 'tournament-deleted');
    }

    public function toggleActive(Request $request, Tournament $tournament): RedirectResponse
    {
        $tournament->update(['active' => ! $tournament->active]);

        activity('tournament')->causedBy($request->user())
            ->performedOn($tournament)->log($tournament->active ? 'tournament.activated' : 'tournament.deactivated');

        return back()->with('status', $tournament->active ? 'tournament-activated' : 'tournament-deactivated');
    }

    public function attachTeam(Request $request, Tournament $tournament): RedirectResponse
    {
        $validated = $request->validate([
            'team_id' => ['required', 'integer', 'exists:teams,id'],
        ]);

        $alreadyRegistered = TournamentTeam::where('tournament_id', $tournament->id)
            ->where('team_id', $validated['team_id'])
            ->exists();

        if ($alreadyRegistered) {
            return back()->with('error', 'tournament-team-already-registered');
        }

        TournamentTeam::create([
            'tournament_id' => $tournament->id,
            'team_id' => $validated['team_id'],
        ]);

        $tournament->touch();

        activity('tournament')->causedBy($request->user())
            ->performedOn($tournament)->log('tournament.team_attached');

        return back()->with('status', 'tournament-team-attached');
    }

    public function detachTeam(Request $request, Tournament $tournament, Team $team): RedirectResponse
    {
        TournamentTeam::where('tournament_id', $tournament->id)->where('team_id', $team->id)->delete();

        $tournament->touch();

        activity('tournament')->causedBy($request->user())
            ->performedOn($tournament)->log('tournament.team_detached');

        return back()->with('status', 'tournament-team-detached');
    }

    private function validateTournament(Request $request, bool $isUpdate = false): array
    {
        $rule = $isUpdate ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$rule, 'string', 'max:255'],
            'region' => [$rule, 'string', 'max:50'],
            'category' => [$rule, 'string', 'max:50'],
            'category_custom' => ['required_if:category,__custom__', 'nullable', 'string', 'max:50'],
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

    /**
     * The category select offers a fixed list plus a "custom" option
     * (mirrors Dashboard's TournamentController::store) — swap in the
     * free-text value when __custom__ was picked.
     */
    private function resolveCustomCategory(array $validated): array
    {
        if (($validated['category'] ?? null) === '__custom__') {
            $validated['category'] = $validated['category_custom'] ?? '';
        }

        unset($validated['category_custom']);

        return $validated;
    }

    private function coreColumns(array $validated): array
    {
        $columns = ['name', 'region', 'category', 'start_date', 'end_date', 'location', 'prize_pool', 'description', 'liquipedia_link', 'status', 'active'];

        return array_intersect_key($validated, array_flip($columns));
    }

    /**
     * Recreate a tournament's phases from an indented name/format/order
     * list (ported from ApiTournamentController::syncPhases), where
     * parent_id may reference either an existing phase id or another
     * entry's position in the submitted array — the phase builder's
     * indent/outdent controls only know positions, not real ids, for
     * phases created in the same request.
     */
    private function syncPhases(Tournament $tournament, array $phases): void
    {
        $keptIds = [];
        $idMap = [];

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

        foreach ($phases as $index => $phase) {
            if (! array_key_exists('parent_id', $phase) || $phase['parent_id'] === null || $phase['parent_id'] === '') {
                continue;
            }

            $parentId = $phase['parent_id'];
            $parentId = $idMap[$parentId] ?? $parentId;

            TournamentPhase::where('id', $idMap[$index])->update(['parent_id' => $parentId]);
        }

        TournamentPhase::where('tournament_id', $tournament->id)
            ->whereNotIn('id', $keptIds)
            ->delete();
    }

    public function quickCreateTeam(Request $request, Tournament $tournament): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $team = DB::transaction(function () use ($tournament, $validated) {
            $team = Team::create(['name' => $validated['name'], 'socials' => []]);

            TournamentTeam::create([
                'tournament_id' => $tournament->id,
                'team_id' => $team->id,
            ]);

            return $team;
        });

        $tournament->touch();

        activity('tournament')->causedBy($request->user())
            ->performedOn($tournament)->log('tournament.team_created_and_attached');

        return back()->with('status', 'tournament-team-created')->with('team_id', $team->id);
    }
}
