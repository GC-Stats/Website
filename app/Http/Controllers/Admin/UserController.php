<?php

/**
 * GC-Stats — Admin: user directory
 *
 * Read-only: a searchable, filterable directory of accounts plus a detail
 * page summarizing everything about one account (global roles, team roles,
 * publisher roles, linked player, sanction history). Editing itself happens
 * on the dedicated screens that already own that logic — Admin\RoleController
 * (global roles), Team\RoleController (team roles) and News\RoleController
 * (publisher roles) — so this controller never mutates anything, only links
 * out to them.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsPublisher;
use App\Models\Team;
use App\Models\User;
use App\Services\TeamRoleService;
use App\Support\PermissionTeam;
use App\Support\PublisherPermissions;
use App\Support\PublisherScope;
use App\Support\UserRoleSummary;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    private const SORTABLE = ['user', 'sanctions', 'joined'];

    public function index(Request $request): View
    {
        $search = $request->get('q');
        $roleFilter = $request->get('role');
        $publisherFilter = $request->get('publisher');

        [$sort, $direction] = $this->resolveSort($request, self::SORTABLE, 'user', 'asc');

        $userIds = null;

        if ($publisherFilter) {
            $userIds = PublisherScope::userIdsForPublishers([(int) $publisherFilter]);
        }

        $users = User::query()
            ->with('roles:id,name')
            ->withCount(['sanctions as active_sanctions_count' => fn ($query) => $query->active()])
            ->when($search, fn ($query) => $query->matching($search))
            ->when($roleFilter, fn ($query) => $query->whereHas('roles', fn ($q) => $q->where('name', $roleFilter)))
            ->when($userIds !== null, fn ($query) => $query->whereIn('id', $userIds))
            ->when($sort === 'sanctions', fn ($query) => $query->orderBy('active_sanctions_count', $direction))
            ->when($sort === 'joined', fn ($query) => $query->orderBy('created_at', $direction))
            ->when($sort === 'user', fn ($query) => $query->orderBy('name', $direction))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $publishers = NewsPublisher::orderBy('name')->get(['id', 'name']);

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search ?? '',
            'roleFilter' => $roleFilter ?? '',
            'publisherFilter' => $publisherFilter ?? '',
            'sort' => $sort,
            'direction' => $direction,
            'roles' => Role::where('team_id', PermissionTeam::GLOBAL_ID)->orderBy('name')->get(),
            'publishers' => $publishers,
            'publisherNamesByUserId' => $this->publisherNamesByUserId($users->pluck('id'), $publishers),
        ]);
    }

    public function show(User $user): View
    {
        $user->load(['roles:id,name', 'socialAccounts', 'passkeys']);

        $teamNames = Team::pluck('name', 'id');
        $publisherNames = NewsPublisher::pluck('name', 'id');

        $player = $user->player?->load(['teams' => fn ($query) => $query->wherePivotNull('left_at')]);

        $sanctions = $user->sanctions()
            ->with(['issuedBy:id,name', 'team:id,name'])
            ->latest()
            ->limit(15)
            ->get();

        return view('admin.users.show', [
            'user' => $user,
            'teamRoles' => UserRoleSummary::rolesGroupedByTeam($user->id, 'web', TeamRoleService::ROLE_OWNER, TeamRoleService::ROLE_MANAGER, TeamRoleService::ROLE_EDITOR)
                ->map(fn ($roleNames, $teamId) => ['name' => $teamNames[$teamId] ?? "#{$teamId}", 'id' => $teamId, 'roles' => $roleNames]),
            'publisherRoles' => UserRoleSummary::rolesGroupedByTeam($user->id, PublisherPermissions::GUARD)
                ->map(fn ($roleNames, $publisherId) => ['name' => $publisherNames[$publisherId] ?? "#{$publisherId}", 'id' => $publisherId, 'roles' => $roleNames]),
            'player' => $player,
            'sanctions' => $sanctions,
        ]);
    }

    /**
     * @param  Collection<int, int>  $userIds
     * @param  Collection<int, NewsPublisher>  $publishers  already-loaded id=>name map, reused from index() rather than re-queried
     * @return array<int, list<string>>
     */
    private function publisherNamesByUserId($userIds, Collection $publishers): array
    {
        $publisherNames = $publishers->pluck('name', 'id');

        return PublisherScope::publisherIdsForUsers($userIds)
            ->map(fn ($publisherIds) => $publisherIds
                ->map(fn ($publisherId) => $publisherNames[$publisherId] ?? null)
                ->filter()
                ->values()
                ->all())
            ->filter(fn ($names) => $names !== [])
            ->all();
    }
}
