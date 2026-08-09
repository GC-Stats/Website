<?php

/**
 * GC-Stats — Admin: user directory
 *
 * Read-only: a searchable, filterable directory of accounts plus a detail
 * page summarizing everything about one account (global roles, organization
 * roles, linked player, sanction history). Editing itself happens on the
 * dedicated screens that already own that logic — Admin\RoleController
 * (global roles) and Organization\RoleController (organization roles) — so
 * this controller never mutates anything, only links out to them.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Public\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Support\OrganizationPermissions;
use App\Support\OrganizationScope;
use App\Support\PermissionTeam;
use App\Support\UserRoleSummary;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
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
        $organizationFilter = $request->get('organization');

        [$sort, $direction] = $this->resolveSort($request, self::SORTABLE, 'user', 'asc');

        $userIds = null;

        if ($organizationFilter) {
            $userIds = OrganizationScope::userIdsForOrganizations([(int) $organizationFilter]);
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

        $organizations = Organization::orderBy('name')->get(['id', 'name']);

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search ?? '',
            'roleFilter' => $roleFilter ?? '',
            'organizationFilter' => $organizationFilter ?? '',
            'sort' => $sort,
            'direction' => $direction,
            'roles' => Role::where('team_id', PermissionTeam::GLOBAL_ID)->orderBy('name')->get(),
            'organizations' => $organizations,
            'organizationNamesByUserId' => $this->organizationNamesByUserId($users->pluck('id'), $organizations),
        ]);
    }

    public function show(Request $request, User $user): View
    {
        $viewer = $request->user();

        $organizationRoles = collect();
        $player = null;
        $sanctions = collect();
        $reportsReceived = collect();
        $reportsSubmitted = collect();

        $isIndependentAuthor = false;

        if ($viewer->can('users.view.details')) {
            $user->load(['roles:id,name', 'socialAccounts', 'passkeys']);

            $organizationNames = Organization::pluck('name', 'id');

            $organizationRoles = UserRoleSummary::rolesGroupedByTeam($user->id, OrganizationPermissions::GUARD)
                ->map(fn ($roleNames, $organizationId) => ['name' => $organizationNames[$organizationId] ?? "#{$organizationId}", 'id' => $organizationId, 'roles' => $roleNames]);

            PermissionTeam::global();
            $isIndependentAuthor = $user->hasPermissionTo('news.author');
        }

        if ($viewer->can('players.view')) {
            $player = $user->player?->load(['teams' => fn ($query) => $query->wherePivotNull('left_at')]);
        }

        if ($viewer->can('sanctions.view')) {
            $sanctions = $user->sanctions()
                ->with(['issuedBy:id,name', 'team:id,name'])
                ->latest()
                ->limit(15)
                ->get();
        }

        if ($viewer->can('reports.view')) {
            $reportsReceived = $user->reportsReceived()
                ->with(['team:id,name', 'reviewedBy:id,name'])
                ->latest()
                ->limit(15)
                ->get();

            $reportsSubmitted = $user->reportsSubmitted()
                ->with(['reportedUser:id,name,username', 'team:id,name'])
                ->latest()
                ->limit(15)
                ->get();
        }

        return view('admin.users.show', [
            'user' => $user,
            'organizationRoles' => $organizationRoles,
            'player' => $player,
            'sanctions' => $sanctions,
            'reportsReceived' => $reportsReceived,
            'reportsSubmitted' => $reportsSubmitted,
            'isIndependentAuthor' => $isIndependentAuthor,
        ]);
    }

    /**
     * Grants/revokes 'news.author' (the site-wide permission behind
     * dashboard/me, see routes/personal-dashboard.php) directly on the user
     * — deliberately not via a Role, unlike every other permission in the
     * app. App\Http\Controllers\Public\AboutController lists anyone holding
     * a *global role* as site staff on the public "About Us" page; an
     * independent author who just wants to publish their own articles isn't
     * staff, so this must never create a model_has_roles row. Gated by the
     * same super-admin-only 'manage-roles' gate as Admin\RoleController,
     * since it's still a permission grant.
     */
    public function toggleNewsAuthor(Request $request, User $user): RedirectResponse
    {
        PermissionTeam::global();

        if ($user->hasPermissionTo('news.author')) {
            $user->revokePermissionTo('news.author');
            $status = 'news-author-revoked';
        } else {
            $user->givePermissionTo('news.author');
            $status = 'news-author-granted';
        }

        activity('administration')->performedOn($user)->causedBy($request->user())
            ->log($status === 'news-author-granted' ? 'news_author_permission.granted' : 'news_author_permission.revoked');

        return back()->with('status', $status);
    }

    /**
     * @param  Collection<int, int>  $userIds
     * @param  Collection<int, Organization>  $organizations  already-loaded id=>name map, reused from index() rather than re-queried
     * @return array<int, list<string>>
     */
    private function organizationNamesByUserId($userIds, Collection $organizations): array
    {
        $organizationNames = $organizations->pluck('name', 'id');

        return OrganizationScope::organizationIdsForUsers($userIds)
            ->map(fn ($organizationIds) => $organizationIds
                ->map(fn ($organizationId) => $organizationNames[$organizationId] ?? null)
                ->filter()
                ->values()
                ->all())
            ->filter(fn ($names) => $names !== [])
            ->all();
    }
}
