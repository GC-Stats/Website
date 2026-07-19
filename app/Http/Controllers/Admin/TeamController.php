<?php

/**
 * GC-Stats — Admin: teams
 *
 * Assigns a team's owner and sets its max_permissions ceiling (the most
 * App\Support\TeamPermissions any of that team's own roles can ever hold —
 * see Team\RoleController, which does the per-role assignment within that
 * ceiling). Gated by `teams.view`/`teams.edit`/`teams.delete`/`teams.merge`.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Exceptions\TeamHasMatchesException;
use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\Services\RosterService;
use App\Services\TeamMergeService;
use App\Services\TeamProfileService;
use App\Services\TeamRoleService;
use App\Support\Countries;
use App\Support\PermissionTeam;
use App\Support\TeamPermissions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

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
        ]);
    }

    public function show(Request $request, Team $team, TeamRoleService $teamRoles, RosterService $rosterService, TeamMergeService $mergeService): View
    {
        $teamRoles->ensureRolesExist($team);

        PermissionTeam::use($team->id);
        $ownerRole = Role::where('team_id', $team->id)->where('name', TeamRoleService::ROLE_OWNER)->first();
        $owners = $ownerRole?->users()->orderBy('name')->get() ?? collect();
        PermissionTeam::global();

        $search = $request->get('q');
        $playerSearch = $request->get('player_q');
        $history = $rosterService->history($team->id);

        $existingOwnerIds = $ownerRole
            ? DB::table('model_has_roles')->where('role_id', $ownerRole->id)->where('model_type', User::class)->pluck('model_id')
            : collect();

        return view('admin.teams.show', [
            'team' => $team,
            'owners' => $owners,
            'hasMatches' => $mergeService->hasMatches($team),
            'countries' => app(Countries::class)->list(),
            'permissionGroups' => TeamPermissions::grouped(),
            'search' => $search ?? '',
            'searchResults' => $search
                ? User::matching($search)
                    ->whereNotIn('id', $existingOwnerIds)
                    ->limit(10)->get()
                : collect(),
            'roster' => $history->whereNull('left_at')->values(),
            'rosterHistory' => $history->whereNotNull('left_at')->values(),
            'playerSearch' => $playerSearch ?? '',
            'playerSearchResults' => $playerSearch
                ? Player::where('handle', 'like', '%'.$this->escapeLike($playerSearch).'%')
                    ->whereNotIn('id', $history->where('left_at', null)->pluck('player_id'))
                    ->limit(10)->get()
                : collect(),
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

    public function updateLogo(Request $request, Team $team, TeamProfileService $service): RedirectResponse
    {
        $validated = $request->validate([
            'logo' => ['required', 'file', 'image', 'max:10240'],
        ]);

        $service->updateLogo($team, $validated['logo'], $request->user());

        return back()->with('status', 'logo-updated');
    }

    public function storeLogoHistory(Request $request, Team $team, TeamProfileService $service): RedirectResponse
    {
        $validated = $request->validate([
            'logo' => ['required', 'file', 'image', 'max:10240'],
            'from' => ['required', 'date'],
            'until' => ['required', 'date', 'after:from'],
        ]);

        $service->addLogoHistoryEntry($team, $validated['logo'], $validated['from'], $validated['until'], $request->user());

        return back()->with('status', 'logo-history-added');
    }

    public function updateLogoEntry(Request $request, Team $team, string $logo, TeamProfileService $service): RedirectResponse
    {
        $validated = $request->validate([
            'from' => ['required', 'date'],
            'until' => ['nullable', 'date', 'after:from'],
        ]);

        $service->updateLogoEntry($team, $logo, $validated['from'], $validated['until'] ?? null, $request->user());

        return back()->with('status', 'logo-history-updated');
    }

    public function destroyLogoEntry(Request $request, Team $team, string $logo, TeamProfileService $service): RedirectResponse
    {
        $service->deleteLogoEntry($team, $logo, $request->user());

        return back()->with('status', 'logo-history-removed');
    }

    public function storeRosterMember(Request $request, Team $team, RosterService $rosterService): RedirectResponse
    {
        $validated = $request->validate([
            'player_id' => ['required', 'integer', 'exists:players,id'],
            'role' => ['nullable', 'string', Rule::in(RosterService::ROLES)],
            'joined_at' => ['required', 'date'],
        ]);

        $rosterService->addMember($team, $validated['player_id'], $validated['role'] ?? null, $validated['joined_at']);

        activity('team')->performedOn($team)->causedBy($request->user())
            ->withProperties(['team_id' => $team->id, 'player_id' => $validated['player_id']])->log('team.roster.member_added');

        return redirect()->route('admin.teams.show', $team)->with('status', 'roster-member-added');
    }

    public function updateRosterMember(Request $request, Team $team, int $entry, RosterService $rosterService): RedirectResponse
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

    public function destroyRosterMember(Request $request, Team $team, int $entry, RosterService $rosterService): RedirectResponse
    {
        $rosterService->deleteEntry($team, $entry);

        activity('team')->performedOn($team)->causedBy($request->user())
            ->withProperties(['team_id' => $team->id, 'entry_id' => $entry])->log('team.roster.entry_removed');

        return back()->with('status', 'roster-entry-removed');
    }

    public function updateMaxPermissions(Request $request, Team $team): RedirectResponse
    {
        $validated = $request->validate([
            'max_permissions' => ['array'],
            'max_permissions.*' => ['string', Rule::in(TeamPermissions::all())],
        ]);

        $ceiling = $validated['max_permissions'] ?? [];
        $team->update(['max_permissions' => $ceiling]);

        PermissionTeam::use($team->id);
        foreach (Role::where('team_id', $team->id)->get() as $role) {
            $permissions = $role->name === TeamRoleService::ROLE_OWNER
                ? $ceiling
                : array_intersect($role->permissions->pluck('name')->all(), $ceiling);

            $role->syncPermissions($permissions);
        }
        PermissionTeam::global();

        activity('team')->performedOn($team)->causedBy($request->user())
            ->withProperties(['max_permissions' => $ceiling])->log('team.max_permissions_updated');

        return back()->with('status', 'max-permissions-updated');
    }

    public function assignOwner(Request $request, Team $team, TeamRoleService $teamRoles): RedirectResponse
    {
        $validated = $request->validate(['user_id' => ['required', 'integer', 'exists:users,id']]);

        $user = User::findOrFail($validated['user_id']);
        $teamRoles->assign($user, $team, TeamRoleService::ROLE_OWNER);

        activity('team')->performedOn($user)->causedBy($request->user())
            ->withProperties(['team_id' => $team->id])->log('team.owner_assigned');

        return redirect()->route('admin.teams.show', $team)->with('status', 'owner-assigned');
    }

    public function removeOwner(Request $request, Team $team, User $user, TeamRoleService $teamRoles): RedirectResponse
    {
        $teamRoles->revoke($user, $team, TeamRoleService::ROLE_OWNER);

        activity('team')->performedOn($user)->causedBy($request->user())
            ->withProperties(['team_id' => $team->id])->log('team.owner_removed');

        return back()->with('status', 'owner-removed');
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
            'roleItems' => $this->roleItemsFor($team),
        ]);
    }

    /**
     * Every (role, user) assignment on $team, for the merge picker — one
     * checkbox per user per role, valued "{role_id}:{user_id}" since
     * model_has_roles has no single-column id of its own.
     */
    private function roleItemsFor(Team $team): Collection
    {
        return DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->join('users', 'users.id', '=', 'model_has_roles.model_id')
            ->where('model_has_roles.team_id', $team->id)
            ->where('model_has_roles.model_type', User::class)
            ->orderBy('roles.name')->orderBy('users.name')
            ->get(['roles.id as role_id', 'roles.name as role_name', 'users.id as user_id', 'users.name as user_name', 'users.username as user_username']);
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
            'roles' => ['array'],
            'roles.*' => ['string', 'regex:/^\d+:\d+$/'],
        ]);

        if ((int) $validated['target_id'] === $team->id) {
            throw ValidationException::withMessages(['target_id' => __('admin.teams.merge.errors.same_team')]);
        }

        $target = Team::findOrFail($validated['target_id']);

        $mergeService->merge($team, $target, [
            'roster' => $validated['roster'] ?? [],
            'tournaments' => $validated['tournaments'] ?? [],
            'news' => $validated['news'] ?? [],
            'roles' => $validated['roles'] ?? [],
            'logos' => $validated['logos'] ?? [],
        ], $request->user());

        return redirect()->route('admin.teams.show', $target)->with('status', 'team-merged');
    }
}
