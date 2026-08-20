<?php

/**
 * GC-Stats — Admin: tournaments
 *
 * CRUD over tournaments and their phases/participating teams. Editing a
 * finished tournament requires the extra `tournaments.finished.edit`
 * permission on top of `tournaments.edit` — see the `update()` guard.
 * Editing, deleting, or managing teams on an inactive tournament (active
 * = false) similarly requires `tournaments.inactive.edit` /
 * `.delete` / `.teams.manage` on top of the base permission.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Public\Controller;
use App\Models\PointType;
use App\Models\Tournament;
use App\Models\TournamentPhase;
use App\Services\LogoUploadService;
use App\Support\Activity\ActivityChangeSet;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class TournamentController extends Controller
{
    public const CATEGORIES = ['Championship', 'Regional', 'Open Qualifier', 'Cash Cups', 'Showmatch', 'Unofficial tournament'];

    private const SORTABLE = ['name', 'start_date', 'region', 'status', 'teams_count'];

    public function index(Request $request): View
    {
        $search = $request->get('q');
        $region = $request->get('region');
        $category = $request->get('category');
        $status = $request->get('status');
        $active = $request->get('active');
        $sort = $request->query('sort', 'start_date');
        if (! in_array($sort, self::SORTABLE, true)) {
            $sort = 'start_date';
        }

        $defaultDirection = $sort === 'name' ? 'asc' : 'desc';
        $direction = $request->query('direction', $defaultDirection);
        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = $defaultDirection;
        }

        $tournaments = Tournament::query()
            ->withCount('teams')
            ->when($search, fn ($query) => $query->where('name', 'like', '%'.$this->escapeLike($search).'%'))
            ->when($region, fn ($query) => $query->where('region', $region))
            ->when($category === '__custom__', fn ($query) => $query->whereNotIn('category', self::CATEGORIES))
            ->when($category && $category !== '__custom__', fn ($query) => $query->where('category', $category))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($active !== null && $active !== '', fn ($query) => $query->where('active', $active === '1'))
            ->when($sort === 'name', fn ($query) => $query->orderBy('name', $direction))
            ->when($sort === 'region', fn ($query) => $query->orderBy('region', $direction))
            ->when($sort === 'status', fn ($query) => $query->orderBy('status', $direction))
            ->when($sort === 'teams_count', fn ($query) => $query->orderBy('teams_count', $direction))
            ->when($sort === 'start_date', fn ($query) => $query->orderBy('start_date', $direction))
            ->orderByDesc('id')
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
            'direction' => $direction,
            'regions' => array_keys(config('regions.riot_api')),
            'categories' => self::CATEGORIES,
        ]);
    }

    public function create(): View
    {
        return view('admin.tournaments.create', [
            'regions' => array_keys(config('regions.riot_api')),
            'categories' => self::CATEGORIES,
            'pointTypes' => PointType::orderBy('name')->get(),
        ]);
    }

    public function show(Tournament $tournament): View
    {
        $tournament->load([
            'rootPhases.children',
            'rootPhases.qualifications.destinationPhase.tournament',
            'rootPhases.children.qualifications.destinationPhase.tournament',
        ]);

        return view('admin.tournaments.show', [
            'tournament' => $tournament,
        ]);
    }

    public function edit(Tournament $tournament): View
    {
        $tournament->load('rootPhases.children');

        return view('admin.tournaments.edit', [
            'tournament' => $tournament,
            'regions' => array_keys(config('regions.riot_api')),
            'categories' => self::CATEGORIES,
            'pointTypes' => PointType::orderBy('name')->get(),
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

        $changeSet = ActivityChangeSet::fromCreated($tournament, array_keys($this->coreColumns($validated)));

        if (! empty($validated['phases'])) {
            $changeSet->add('phases', null, $this->phaseSnapshot($tournament));
        }

        activity('tournament')->causedBy($request->user())
            ->performedOn($tournament)
            ->withProperties($changeSet->toArray())
            ->log('tournament.created');

        return redirect()->route('admin.tournaments.show', $tournament)->with('status', 'tournament-created');
    }

    public function update(Request $request, Tournament $tournament): RedirectResponse
    {
        abort_unless(
            $tournament->status !== 'finished' || $request->user()->can('tournaments.finished.edit'),
            403,
            __('admin.tournaments.finished_locked')
        );

        abort_unless(
            $tournament->active || $request->user()->can('tournaments.inactive.edit'),
            403,
            __('admin.tournaments.inactive_locked')
        );

        $validated = $this->resolveCustomCategory($this->validateTournament($request, true));

        $phasesBefore = isset($validated['phases']) ? $this->phaseSnapshot($tournament) : null;

        DB::transaction(function () use ($tournament, $validated) {
            $tournament->update($this->coreColumns($validated, true));

            if (isset($validated['phases'])) {
                $this->syncPhases($tournament, $validated['phases']);
            }
        });

        $changeSet = ActivityChangeSet::fromModel($tournament, array_keys($this->coreColumns($validated)));

        if ($phasesBefore !== null) {
            $changeSet->add('phases', $phasesBefore, $this->phaseSnapshot($tournament));
        }

        activity('tournament')->causedBy($request->user())
            ->performedOn($tournament)
            ->withProperties($changeSet->toArray())
            ->log('tournament.updated');

        return redirect()->route('admin.tournaments.show', $tournament)->with('status', 'tournament-updated');
    }

    public function destroy(Request $request, Tournament $tournament): RedirectResponse
    {
        abort_unless(
            $tournament->active || $request->user()->can('tournaments.inactive.delete'),
            403,
            __('admin.tournaments.inactive_delete_locked')
        );

        $name = $tournament->name;
        $tournament->delete();

        activity('tournament')->causedBy($request->user())
            ->withProperties(['name' => $name])
            ->log('tournament.deleted');

        return redirect()->route('admin.tournaments.index')->with('status', 'tournament-deleted');
    }

    public function toggleActive(Request $request, Tournament $tournament): RedirectResponse
    {
        $tournament->update(['active' => ! $tournament->active]);

        activity('tournament')->causedBy($request->user())
            ->performedOn($tournament)->log($tournament->active ? 'tournament.activated' : 'tournament.deactivated');

        return back()->with('status', $tournament->active ? 'tournament-activated' : 'tournament-deactivated');
    }

    public function updateLogo(Request $request, Tournament $tournament, LogoUploadService $logoUploadService): RedirectResponse
    {
        $validated = $request->validate([
            'logo' => ['required', 'file', 'image', 'max:10240'],
            'theme' => ['nullable', 'in:dark,light'],
        ]);

        $uuid = $logoUploadService->storeLogoPair($validated['logo'], 'tournaments');
        $logoUploadService->acceptWithHistory($tournament, 'tournament', $uuid, theme: $validated['theme'] ?? null);

        activity('tournament')->causedBy($request->user())
            ->performedOn($tournament)
            ->withProperties(['logo_id' => $uuid, 'theme' => $validated['theme'] ?? null])
            ->log('tournament.logo_updated');

        return back()->with('status', 'logo-updated');
    }

    public function storeLogoHistory(Request $request, Tournament $tournament, LogoUploadService $logoUploadService): RedirectResponse
    {
        $validated = $request->validate([
            'logo' => ['required', 'file', 'image', 'max:10240'],
            'from' => ['required', 'date'],
            'until' => ['required', 'date', 'after:from'],
            'theme' => ['nullable', 'in:dark,light'],
            'is_visible' => ['nullable', 'boolean'],
        ]);

        $uuid = $logoUploadService->storeLogoPair($validated['logo'], 'tournaments');
        $logoUploadService->acceptWithHistory($tournament, 'tournament', $uuid, $validated['from'], $validated['until'], $validated['theme'] ?? null, $request->boolean('is_visible'));

        activity('tournament')->causedBy($request->user())
            ->performedOn($tournament)
            ->withProperties(['logo_id' => $uuid, 'from' => $validated['from'], 'until' => $validated['until'], 'theme' => $validated['theme'] ?? null, 'is_visible' => $request->boolean('is_visible')])
            ->log('tournament.logo_history_added');

        return back()->with('status', 'logo-history-added');
    }

    public function updateLogoEntry(Request $request, Tournament $tournament, string $logo, LogoUploadService $logoUploadService): RedirectResponse
    {
        $validated = $request->validate([
            'from' => ['required', 'date'],
            'until' => ['nullable', 'date', 'after:from'],
            'theme' => ['nullable', 'in:dark,light'],
            'is_visible' => ['nullable', 'boolean'],
        ]);

        $logoModel = $tournament->logos()->findOrFail($logo);
        $logoModel->update(['from' => $validated['from'], 'until' => $validated['until'] ?? null, 'theme' => $validated['theme'] ?? null, 'is_visible' => $request->boolean('is_visible')]);

        activity('tournament')->causedBy($request->user())
            ->performedOn($tournament)
            ->withProperties(ActivityChangeSet::fromModel($logoModel, ['from', 'until', 'theme', 'is_visible'])->mergeInto(['logo_id' => $logo]))
            ->log('tournament.logo_history_updated');

        return back()->with('status', 'logo-history-updated');
    }

    public function destroyLogoEntry(Request $request, Tournament $tournament, string $logo, LogoUploadService $logoUploadService): RedirectResponse
    {
        $logoModel = $tournament->logos()->findOrFail($logo);
        $logoUploadService->deleteFiles('tournaments', $logoModel->id);
        $logoModel->delete();

        activity('tournament')->causedBy($request->user())
            ->performedOn($tournament)
            ->withProperties(['logo_id' => $logo])
            ->log('tournament.logo_history_removed');

        return back()->with('status', 'logo-history-removed');
    }

    private function validateTournament(Request $request, bool $isUpdate = false): array
    {
        $rule = $isUpdate ? 'sometimes' : 'required';

        $request->merge(['point_type_id' => $request->input('point_type_id') ?: null]);

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
            'point_type_id' => ['sometimes', 'nullable', 'integer', 'exists:point_types,id'],
            'phases' => ['sometimes', 'array'],
            'phases.*.id' => ['sometimes', 'nullable', 'integer', 'exists:tournament_phases,id'],
            'phases.*.name' => ['required_with:phases', 'string', 'max:255'],
            'phases.*.format' => ['sometimes', 'nullable', 'string', 'max:100'],
            'phases.*.parent_id' => ['sometimes', 'nullable', 'integer'],
            'phases.*.order' => ['sometimes', 'integer'],
            'phases.*.start_date' => ['sometimes', 'nullable', 'date'],
            'phases.*.end_date' => ['sometimes', 'nullable', 'date'],
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
        $columns = ['name', 'region', 'category', 'start_date', 'end_date', 'location', 'prize_pool', 'description', 'liquipedia_link', 'status', 'active', 'point_type_id'];

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
            $isChild = ! empty($phase['parent_id'] ?? null);
            $dates = [
                'start_date' => $isChild ? null : ($phase['start_date'] ?? null),
                'end_date' => $isChild ? null : ($phase['end_date'] ?? null),
            ];

            if (! empty($phase['id'])) {
                $model = TournamentPhase::where('tournament_id', $tournament->id)->find($phase['id']);

                if ($model) {
                    $model->update([
                        'name' => $phase['name'],
                        'format' => $phase['format'] ?? null,
                        'order' => $phase['order'] ?? 1,
                        ...$dates,
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
                ...$dates,
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

            // parent_id has a DB-level FK constraint (see 0008_tournaments_phases.php)
            // and 'phases.*.parent_id' is only validated as a bare integer above
            // (it can't be a plain exists:tournament_phases,id rule since it may
            // instead reference another phase's position within this same
            // submitted batch, resolved via $idMap just above) — so a garbage or
            // cross-tournament id here would otherwise throw an uncaught
            // QueryException. Only accept ids that are actually part of this
            // tournament's own just-processed phase set.
            if (! in_array($parentId, $keptIds, true)) {
                continue;
            }

            TournamentPhase::where('id', $idMap[$index])->update(['parent_id' => $parentId]);
        }

        TournamentPhase::where('tournament_id', $tournament->id)
            ->whereNotIn('id', $keptIds)
            ->delete();
    }

    /**
     * Snapshot of a tournament's phases for activity logging — taken
     * before and after syncPhases() so the log can show exactly which
     * phases were added, removed, renamed, reordered or reparented.
     *
     * @return list<array<string, mixed>>
     */
    private function phaseSnapshot(Tournament $tournament): array
    {
        return $tournament->phases()->orderBy('order')->get()
            ->map(fn (TournamentPhase $phase) => Arr::only($phase->toArray(), [
                'id', 'name', 'format', 'order', 'parent_id', 'start_date', 'end_date',
            ]))
            ->all();
    }
}
