<?php

/**
 * GC-Stats — Admin: teams
 *
 * Assigns a team's owner and sets its max_permissions ceiling (the most
 * App\Support\TeamPermissions any of that team's own roles can ever hold —
 * see Team\RoleController, which does the per-role assignment within that
 * ceiling). Gated by `teams.manage`.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use App\Services\TeamRoleService;
use App\Support\PermissionTeam;
use App\Support\TeamPermissions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class TeamController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->get('q');

        $teams = Team::query()
            ->when($search, fn ($q) => $q->where('name', 'like', '%'.$this->escapeLike($search).'%'))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.teams.index', ['teams' => $teams, 'search' => $search ?? '']);
    }

    public function show(Request $request, Team $team, TeamRoleService $teamRoles): View
    {
        $teamRoles->ensureRolesExist($team);

        PermissionTeam::use($team->id);
        $ownerRole = Role::where('team_id', $team->id)->where('name', TeamRoleService::ROLE_OWNER)->first();
        $owners = $ownerRole?->users()->orderBy('name')->get() ?? collect();
        PermissionTeam::global();

        $search = $request->get('q');

        // Deliberately not $user->roles() / whereHas('roles', ...): that
        // relation is scoped to whatever PermissionTeam context is
        // currently active, not $ownerRole's own team — querying the
        // pivot table directly here avoids that mismatch entirely.
        $existingOwnerIds = $ownerRole
            ? DB::table('model_has_roles')->where('role_id', $ownerRole->id)->where('model_type', User::class)->pluck('model_id')
            : collect();

        return view('admin.teams.show', [
            'team' => $team,
            'owners' => $owners,
            'permissionGroups' => TeamPermissions::grouped(),
            'search' => $search ?? '',
            'searchResults' => $search
                ? User::where(fn ($q) => $q->where('name', 'like', '%'.$this->escapeLike($search).'%')->orWhere('email', 'like', '%'.$this->escapeLike($search).'%'))
                    ->whereNotIn('id', $existingOwnerIds)
                    ->limit(10)->get()
                : collect(),
        ]);
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
            // team_owner always tracks the ceiling exactly: it's the
            // team's "full access" role by definition (TeamRoleService),
            // so widening the ceiling must widen owner along with it —
            // otherwise a team whose roles were first provisioned before
            // any ceiling was set (owner synced to an empty ceiling, see
            // TeamRoleService::ensureRolesExist) stays permission-less
            // forever, since intersecting can only ever narrow, never add.
            // Every other role keeps whatever subset an admin deliberately
            // chose for it, just trimmed back if it now exceeds the ceiling.
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

        // Not back(): the referer still carries the search modal's ?q=...,
        // which would reopen it (see <x-modal :open-by-default>) right
        // back onto a now-stale result list. Redirecting to the clean URL
        // closes it.
        return redirect()->route('admin.teams.show', $team)->with('status', 'owner-assigned');
    }

    public function removeOwner(Request $request, Team $team, User $user, TeamRoleService $teamRoles): RedirectResponse
    {
        $teamRoles->revoke($user, $team, TeamRoleService::ROLE_OWNER);

        activity('team')->performedOn($user)->causedBy($request->user())
            ->withProperties(['team_id' => $team->id])->log('team.owner_removed');

        return back()->with('status', 'owner-removed');
    }
}
