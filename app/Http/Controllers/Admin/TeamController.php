<?php

/**
 * GC-Stats — Admin: teams
 *
 * Full team management: profile/logo/tags/roster editing, merging, and
 * deletion. There's no self-service equivalent — every field here is
 * staff-only; a non-staff user's only way to affect a team's data is
 * Auth\TeamChangeRequestController's suggestion queue (same as players).
 * Gated by `teams.view`/`teams.edit`/`teams.delete`/`teams.merge`.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Exceptions\TeamHasMatchesException;
use App\Http\Controllers\Public\Controller;
use App\Models\Team;
use App\Services\RosterService;
use App\Services\TeamMergeService;
use App\Services\TeamProfileService;
use App\Support\Activity\ActivityChangeSet;
use App\Support\Countries;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TeamController extends Controller
{
    /**
     * Correlated subquery for a team's most recent match date, across
     * either side (team_a_id/team_b_id). Built as two UNION ALL branches
     * rather than a single OR so each half can still use its own existing
     * composite index (idx_matches_team_a_scheduled / _team_b_scheduled).
     */
    private function latestMatchSubquery(): string
    {
        return '(SELECT MAX(scheduled_at) FROM ('
            .'SELECT scheduled_at FROM matches WHERE matches.team_a_id = teams.id '
            .'UNION ALL '
            .'SELECT scheduled_at FROM matches WHERE matches.team_b_id = teams.id'
            .') recent_matches)';
    }

    private const ACTIVE_WITHIN_WINDOWS = [
        '30d' => '30 days',
        '90d' => '90 days',
        '6m' => '6 months',
        '1y' => '1 year',
    ];

    public function index(Request $request): View
    {
        $search = $request->get('q');
        $sort = $request->get('sort', 'name');
        $activeWithin = $request->get('active_within');

        $teams = Team::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$this->escapeLike($search).'%');

                    if (ctype_digit($search)) {
                        $query->orWhere('id', (int) $search)->orWhere('vlr_id', (int) $search);
                    }
                });
            })
            ->when(
                $activeWithin && array_key_exists($activeWithin, self::ACTIVE_WITHIN_WINDOWS),
                fn ($query) => $query->whereRaw(
                    $this->latestMatchSubquery().' >= ?',
                    [now()->sub(self::ACTIVE_WITHIN_WINDOWS[$activeWithin])]
                )
            )
            ->when($sort === 'country', fn ($query) => $query->orderBy('country_code'))
            ->when($sort === 'recent_activity', fn ($query) => $query->orderByRaw($this->latestMatchSubquery().' DESC'))
            ->when($sort === 'name', fn ($query) => $query->orderBy('name'))
            ->paginate(25)
            ->withQueryString();

        return view('admin.teams.index', [
            'teams' => $teams,
            'search' => $search ?? '',
            'sort' => $sort,
            'activeWithin' => $activeWithin ?? '',
            'countries' => app(Countries::class)->list(),
        ]);
    }

    /**
     * Quick creation from the admin teams list — only the name is
     * required, country/VLR ID can be filled in later from the team's
     * own edit page.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:teams,name'],
            'country_code' => ['nullable', 'string', 'max:5'],
            'vlr_id' => ['nullable', 'integer'],
        ]);

        $team = Team::create([
            'name' => $validated['name'],
            'country_code' => $validated['country_code'] ?? null,
            'vlr_id' => $validated['vlr_id'] ?? null,
            'is_active' => true,
        ]);

        activity('team')->performedOn($team)->causedBy($request->user())
            ->withProperties(ActivityChangeSet::fromCreated($team, ['name', 'country_code', 'vlr_id', 'is_active'])->toArray())
            ->log('team.created');

        return redirect()->route('admin.teams.index')->with('status', 'team-created')->with('created_team', $team->id);
    }

    public function show(Request $request, Team $team, RosterService $rosterService, TeamMergeService $mergeService): View
    {
        $history = $rosterService->history($team->id);

        return view('admin.teams.show', [
            'team' => $team,
            'hasMatches' => $mergeService->hasMatches($team),
            'countries' => app(Countries::class)->list(),
            'roster' => $history->whereNull('left_at')->values(),
            'rosterHistory' => $history->whereNotNull('left_at')->values(),
            'nameHistory' => $team->nameHistory()->get(),
        ]);
    }

    public function updateProfile(Request $request, Team $team, TeamProfileService $service): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:50'],
            'country_code' => ['nullable', 'string', 'max:5'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'vlr_id' => ['nullable', 'integer'],
            'liquipedia_link' => ['nullable', 'url', 'max:255'],
            'socials' => ['nullable', 'array'],
            'socials.website' => ['nullable', 'url', 'max:255'],
            'socials.*' => ['nullable', 'string', 'max:255'],
        ]);

        $service->updateProfile($team, $validated, $request->user());

        return back()->with('status', 'profile-updated');
    }

    public function updateTags(Request $request, Team $team, TeamProfileService $service): RedirectResponse
    {
        $validated = $request->validate([
            'tags' => ['nullable', 'array'],
            'tags.*' => ['nullable', 'string', 'max:50'],
        ]);

        $service->updateTags($team, $validated['tags'] ?? [], $request->user());

        return back()->with('status', 'tags-updated');
    }

    public function updateLogo(Request $request, Team $team, TeamProfileService $service): RedirectResponse
    {
        $validated = $request->validate([
            'logo' => ['required', 'file', 'image', 'max:10240'],
            'theme' => ['nullable', 'in:dark,light'],
        ]);

        $service->updateLogo($team, $validated['logo'], $request->user(), $validated['theme'] ?? null);

        return back()->with('status', 'logo-updated');
    }

    public function storeLogoHistory(Request $request, Team $team, TeamProfileService $service): RedirectResponse
    {
        $validated = $request->validate([
            'logo' => ['required', 'file', 'image', 'max:10240'],
            'from' => ['required', 'date'],
            'until' => ['required', 'date', 'after:from'],
            'theme' => ['nullable', 'in:dark,light'],
            'is_visible' => ['nullable', 'boolean'],
        ]);

        $service->addLogoHistoryEntry($team, $validated['logo'], $validated['from'], $validated['until'], $request->user(), $validated['theme'] ?? null, $request->boolean('is_visible'));

        return back()->with('status', 'logo-history-added');
    }

    public function updateLogoEntry(Request $request, Team $team, string $logo, TeamProfileService $service): RedirectResponse
    {
        $validated = $request->validate([
            'from' => ['required', 'date'],
            'until' => ['nullable', 'date', 'after:from'],
            'theme' => ['nullable', 'in:dark,light'],
            'is_visible' => ['nullable', 'boolean'],
        ]);

        $service->updateLogoEntry($team, $logo, $validated['from'], $validated['until'] ?? null, $request->user(), $validated['theme'] ?? null, $request->boolean('is_visible'));

        return back()->with('status', 'logo-history-updated');
    }

    public function destroyLogoEntry(Request $request, Team $team, string $logo, TeamProfileService $service): RedirectResponse
    {
        $service->deleteLogoEntry($team, $logo, $request->user());

        return back()->with('status', 'logo-history-removed');
    }

    public function storeNameHistory(Request $request, Team $team, TeamProfileService $service): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'from' => ['required', 'date'],
            'until' => ['required', 'date', 'after:from'],
            'is_visible' => ['nullable', 'boolean'],
        ]);

        $service->addNameHistoryEntry($team, $validated['name'], $validated['from'], $validated['until'], $request->boolean('is_visible'), $request->user());

        return back()->with('status', 'name-history-added');
    }

    public function updateNameHistoryEntry(Request $request, Team $team, string $nameHistory, TeamProfileService $service): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'from' => ['required', 'date'],
            'until' => ['nullable', 'date', 'after:from'],
            'is_visible' => ['nullable', 'boolean'],
        ]);

        $service->updateNameHistoryEntry($team, $nameHistory, $validated['name'], $validated['from'], $validated['until'] ?? null, $request->boolean('is_visible'), $request->user());

        return back()->with('status', 'name-history-updated');
    }

    public function destroyNameHistoryEntry(Request $request, Team $team, string $nameHistory, TeamProfileService $service): RedirectResponse
    {
        $service->deleteNameHistoryEntry($team, $nameHistory, $request->user());

        return back()->with('status', 'name-history-removed');
    }

    public function syncRoster(Request $request, Team $team, RosterService $rosterService): RedirectResponse
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

        $before = $this->rosterSnapshot($rosterService, $team);
        $rosterService->save('team_id', $team->id, $entries);
        $after = $this->rosterSnapshot($rosterService, $team);

        activity('team')->performedOn($team)->causedBy($request->user())
            ->withProperties(['team_id' => $team->id, 'changes' => $rosterService->diff($before, $after, 'player_handle')])
            ->log('team.roster.synced');

        return back()->with('status', 'roster-synced');
    }

    private function rosterSnapshot(RosterService $rosterService, Team $team): array
    {
        return $rosterService->history($team->id)
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function destroy(Request $request, Team $team, TeamMergeService $mergeService): RedirectResponse
    {
        try {
            $mergeService->delete($team, $request->user());
        } catch (TeamHasMatchesException) {
            return redirect()->route('admin.teams.show', $team)->with('error', 'team-delete-blocked');
        }

        return redirect()->route('admin.teams.index')->with('status', 'team-deleted');
    }

    public function showMerge(Request $request, Team $team, RosterService $rosterService): View
    {
        $search = $request->get('q');

        return view('admin.teams.merge', [
            'team' => $team,
            'search' => $search ?? '',
            'searchResults' => $search
                ? Team::where('id', '!=', $team->id)
                    ->where('name', 'like', '%'.$this->escapeLike($search).'%')
                    ->limit(10)->get()
                : collect(),
            'rosterItems' => $rosterService->history($team->id)->values(),
            'tournamentItems' => $team->tournaments()->orderByDesc('tournaments.id')->get(['tournaments.id', 'tournaments.name']),
            'newsItems' => $team->news()->orderByDesc('news.id')->get(['news.id', 'news.title']),
            'logoItems' => $team->logos()->orderByDesc('from')->get(),
        ]);
    }

    public function merge(Request $request, Team $team, TeamMergeService $mergeService): RedirectResponse
    {
        $validated = $request->validate([
            'target_id' => ['required', 'integer', 'exists:teams,id'],
            'roster' => ['array'],
            'roster.*' => ['integer'],
            'tournaments' => ['array'],
            'tournaments.*' => ['integer'],
            'news' => ['array'],
            'news.*' => ['integer'],
            'logos' => ['array'],
            'logos.*' => ['string'],
        ]);

        if ((int) $validated['target_id'] === $team->id) {
            throw ValidationException::withMessages(['target_id' => __('admin.teams.merge.errors.same_team')]);
        }

        $target = Team::findOrFail($validated['target_id']);

        $mergeService->merge($team, $target, [
            'roster' => $validated['roster'] ?? [],
            'tournaments' => $validated['tournaments'] ?? [],
            'news' => $validated['news'] ?? [],
            'logos' => $validated['logos'] ?? [],
        ], $request->user());

        return redirect()->route('admin.teams.show', $target)->with('status', 'team-merged');
    }
}
