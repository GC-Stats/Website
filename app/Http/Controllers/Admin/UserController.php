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
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->get('q');
        $roleFilter = $request->get('role');
        $publisherFilter = $request->get('publisher');

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
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $publishers = NewsPublisher::orderBy('name')->get(['id', 'name']);

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search ?? '',
            'roleFilter' => $roleFilter ?? '',
            'publisherFilter' => $publisherFilter ?? '',
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
            'teamRoles' => $this->rolesGroupedByTeam($user->id, 'web', TeamRoleService::ROLE_OWNER, TeamRoleService::ROLE_MANAGER, TeamRoleService::ROLE_EDITOR)
                ->map(fn ($roleNames, $teamId) => ['name' => $teamNames[$teamId] ?? "#{$teamId}", 'id' => $teamId, 'roles' => $roleNames]),
            'publisherRoles' => $this->rolesGroupedByTeam($user->id, PublisherPermissions::GUARD)
                ->map(fn ($roleNames, $publisherId) => ['name' => $publisherNames[$publisherId] ?? "#{$publisherId}", 'id' => $publisherId, 'roles' => $roleNames]),
            'player' => $player,
            'sanctions' => $sanctions,
        ]);
    }

    /**
     * Every role this user holds under the given guard, excluding the
     * global context (team_id 0 — see PermissionTeam), grouped by the
     * team/publisher id it's scoped to. Optionally narrowed to specific
     * role names (used to pick out just the fixed team_owner/manager/editor
     * trio and ignore anything else that might share the guard).
     *
     * @return Collection<int, list<string>>
     */
    private function rolesGroupedByTeam(int $userId, string $guard, string ...$roleNames): Collection
    {
        return DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_id', $userId)
            ->where('model_has_roles.model_type', User::class)
            ->where('roles.guard_name', $guard)
            ->where('model_has_roles.team_id', '!=', PermissionTeam::GLOBAL_ID)
            ->when($roleNames !== [], fn ($query) => $query->whereIn('roles.name', $roleNames))
            ->select('model_has_roles.team_id', 'roles.name')
            ->get()
            ->groupBy('team_id')
            ->map(fn ($rows) => $rows->pluck('name')->all());
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
