<?php

/**
 * GC-Stats — Team: role management
 *
 * A per-team mirror of Admin\RoleController — same page shape (permission
 * matrix, members, custom roles), scoped to one team's own roles instead
 * of the site's global ones. Reached via the 'team.roles.manage'
 * permission, which a team's own team_owner holds by default (see
 * TeamRoleService) but isn't hardcoded — a site admin can grant or revoke
 * it per team, independently of every other team.
 *
 * Every method takes an explicit `string $slug` parameter even though it's
 * unused: with the {team}/{slug}/roles/{role} URI shape, a route segment
 * with no matching method parameter shifts Laravel's positional resolution
 * of any later plain-typed parameter (and, empirically, confuses implicit
 * model binding for the parameter after it too) — {role} was silently
 * receiving {slug}'s value. Declaring $slug keeps every parameter aligned
 * with its route segment.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use App\Services\TeamRoleService;
use App\Support\TeamPermissions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(Team $team, string $slug, TeamRoleService $teamRoles): View
    {
        $teamRoles->ensureRolesExist($team);

        return view('team.roles.index', [
            'team' => $team,
            'roles' => Role::withCount('users')->where('team_id', $team->id)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, Team $team, string $slug): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('roles', 'name')->where('team_id', $team->id)],
        ]);

        $role = Role::create(['name' => $validated['name']]);

        activity('team')->causedBy($request->user())
            ->withProperties(['team_id' => $team->id, 'role' => $role->name])->log('team_role.created');

        return redirect()->route('teams.roles.show', [$team, $team->routeSlug(), $role])->with('status', 'role-created');
    }

    public function show(Request $request, Team $team, string $slug, Role $role): View
    {
        $this->ensureBelongsToTeam($team, $role);

        $search = $request->get('q');

        return view('team.roles.show', [
            'team' => $team,
            'role' => $role,
            'permissionGroups' => TeamPermissions::groupedWithin($team->maxPermissions()),
            'members' => $role->users()->orderBy('name')->get(),
            'search' => $search ?? '',
            'searchResults' => $search
                ? User::matching($search)
                    ->whereDoesntHave('roles', fn ($q) => $q->where('id', $role->id))
                    ->limit(10)->get()
                : collect(),
        ]);
    }

    public function update(Request $request, Team $team, string $slug, Role $role): RedirectResponse
    {
        $this->ensureBelongsToTeam($team, $role);

        // team_owner always mirrors the team's max_permissions ceiling
        // (TeamRoleService, Admin\TeamController::updateMaxPermissions) —
        // it isn't an independently editable set.
        if ($role->name === TeamRoleService::ROLE_OWNER) {
            throw ValidationException::withMessages([
                'role' => __('team.roles.errors.owner_role_protected'),
            ]);
        }

        $validated = $request->validate([
            'permissions' => ['array'],
            // Bounded by the team's own max_permissions ceiling (set by a
            // site admin, see Admin\TeamController), not the full catalog
            // — a role can never exceed what its team is allowed at all.
            'permissions.*' => ['string', Rule::in($team->maxPermissions())],
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        activity('team')->causedBy($request->user())
            ->withProperties(['team_id' => $team->id, 'role' => $role->name, 'permissions' => $validated['permissions'] ?? []])
            ->log('team_role.permissions_updated');

        return back()->with('status', 'permissions-updated');
    }

    public function destroy(Request $request, Team $team, string $slug, Role $role): RedirectResponse
    {
        $this->ensureBelongsToTeam($team, $role);

        if ($role->name === TeamRoleService::ROLE_OWNER) {
            throw ValidationException::withMessages([
                'role' => __('team.roles.errors.owner_role_protected'),
            ]);
        }

        $name = $role->name;
        $role->delete();

        activity('team')->causedBy($request->user())
            ->withProperties(['team_id' => $team->id, 'role' => $name])->log('team_role.deleted');

        return redirect()->route('teams.roles.index', [$team, $team->routeSlug()])->with('status', 'role-deleted');
    }

    public function addMember(Request $request, Team $team, string $slug, Role $role): RedirectResponse
    {
        $this->ensureBelongsToTeam($team, $role);

        // team_owner grants the team's full max_permissions ceiling —
        // handing it out is a site-admin action (Admin\TeamController::
        // assignOwner), not something a team.roles.manage holder should be
        // able to grant to themselves or anyone else via the roles page.
        if ($role->name === TeamRoleService::ROLE_OWNER) {
            throw ValidationException::withMessages([
                'role' => __('team.roles.errors.owner_role_protected'),
            ]);
        }

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $user = User::findOrFail($validated['user_id']);
        $user->assignRole($role);

        activity('team')->performedOn($user)->causedBy($request->user())
            ->withProperties(['team_id' => $team->id, 'role' => $role->name])->log('team_role.assigned');

        // Not back(): the referer still carries the search modal's ?q=...,
        // which would reopen it (see <x-modal :open-by-default>) right
        // back onto a now-stale result list. Redirecting to the clean URL
        // closes it.
        return redirect()->route('teams.roles.show', [$team, $team->routeSlug(), $role])->with('status', 'role-assigned');
    }

    public function removeMember(Request $request, Team $team, string $slug, Role $role, User $user): RedirectResponse
    {
        $this->ensureBelongsToTeam($team, $role);

        if ($role->name === TeamRoleService::ROLE_OWNER && $role->users()->count() <= 1) {
            throw ValidationException::withMessages([
                'role' => __('team.roles.errors.last_owner'),
            ]);
        }

        $user->removeRole($role);

        activity('team')->performedOn($user)->causedBy($request->user())
            ->withProperties(['team_id' => $team->id, 'role' => $role->name])->log('team_role.removed');

        return back()->with('status', 'role-removed');
    }

    /**
     * A role reaching here belonging to a *different* team (e.g. a stale
     * bookmarked URL, or the {role} id simply doesn't belong to {team})
     * 404s rather than being mutated under the wrong team's context.
     */
    private function ensureBelongsToTeam(Team $team, Role $role): void
    {
        abort_unless((int) $role->team_id === $team->id, 404);
    }
}
