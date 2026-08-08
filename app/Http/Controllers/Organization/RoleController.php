<?php

/**
 * GC-Stats — Organization: role management
 *
 * A per-organization mirror of News\RoleController (see its docblock) — same
 * page shape (permission matrix, members, custom roles), scoped to one
 * organization's own roles instead of a publisher's. Reached via the
 * 'organization.roles.manage' permission (guard 'organization', see
 * App\Support\OrganizationPermissions), which an organization's own
 * organization_owner holds by default (see OrganizationRoleService) but
 * isn't hardcoded — a site admin can grant or revoke it per organization,
 * independently of every other organization.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Public\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationRoleService;
use App\Support\Activity\ActivityChangeSet;
use App\Support\OrganizationPermissions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * This controller backs two route groups sharing identical business
     * logic — admin.organizations.roles.* (site-admin, extends admin.layout)
     * and organization-dashboard.roles.* (the organization's own dashboard,
     * extends organization.layout) — see resources/views/organizations/roles/
     * _index.blade.php and _show.blade.php, the shared content both wrapper
     * views @include. Redirects/views pick the matching wrapper based on
     * which route actually matched, so the same controller code serves both.
     */
    private function isDashboard(): bool
    {
        return request()->routeIs('organization-dashboard.*');
    }

    private function routePrefix(): string
    {
        return $this->isDashboard() ? 'organization-dashboard.roles.' : 'admin.organizations.roles.';
    }

    private function viewName(string $page): string
    {
        return $this->isDashboard() ? "organization.dashboard.roles.{$page}" : "admin.organizations.roles.{$page}";
    }

    public function index(Organization $organization, OrganizationRoleService $organizationRoles): View
    {
        $organizationRoles->ensureRolesExist($organization);

        return view($this->viewName('index'), [
            'organization' => $organization,
            'roles' => Role::withCount('users')->where('team_id', $organization->id)
                ->where('guard_name', OrganizationPermissions::GUARD)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, Organization $organization): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'regex:/\S/',
                Rule::unique('roles', 'name')->where('team_id', $organization->id)->where('guard_name', OrganizationPermissions::GUARD)],
        ]);

        $role = Role::create(['name' => $validated['name'], 'guard_name' => OrganizationPermissions::GUARD]);

        activity('organization')->causedBy($request->user())
            ->withProperties(['organization_id' => $organization->id, 'role' => $role->name])->log('organization_role.created');

        return redirect()->route($this->routePrefix().'show', [$organization, $role])->with('status', 'role-created');
    }

    public function show(Request $request, Organization $organization, Role $role): View
    {
        $this->ensureBelongsToOrganization($organization, $role);

        $search = $request->get('q');

        return view($this->viewName('show'), [
            'organization' => $organization,
            'role' => $role,
            'permissionGroups' => OrganizationPermissions::groupedWithin($organization->maxPermissions()),
            'members' => $role->users()->orderBy('name')->get(),
            'search' => $search ?? '',
            'searchResults' => $search
                ? User::matching($search)
                    ->whereDoesntHave('roles', fn ($q) => $q->where('id', $role->id))
                    ->limit(10)->get()
                : collect(),
        ]);
    }

    public function update(Request $request, Organization $organization, Role $role): RedirectResponse
    {
        $this->ensureBelongsToOrganization($organization, $role);

        if ($role->name === OrganizationRoleService::ROLE_OWNER) {
            throw ValidationException::withMessages([
                'role' => __('admin.organizations.roles.errors.owner_role_protected'),
            ]);
        }

        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in($organization->maxPermissions())],
        ]);

        $before = $role->permissions->pluck('name')->sort()->values()->all();
        $role->syncPermissions($validated['permissions'] ?? []);
        $after = collect($validated['permissions'] ?? [])->sort()->values()->all();

        activity('organization')->causedBy($request->user())
            ->withProperties(ActivityChangeSet::make()->add('permissions', $before, $after)
                ->mergeInto(['organization_id' => $organization->id, 'role' => $role->name]))
            ->log('organization_role.permissions_updated');

        return back()->with('status', 'permissions-updated');
    }

    public function destroy(Request $request, Organization $organization, Role $role): RedirectResponse
    {
        $this->ensureBelongsToOrganization($organization, $role);

        if ($role->name === OrganizationRoleService::ROLE_OWNER) {
            throw ValidationException::withMessages([
                'role' => __('admin.organizations.roles.errors.owner_role_protected'),
            ]);
        }

        $name = $role->name;
        $role->delete();

        activity('organization')->causedBy($request->user())
            ->withProperties(['organization_id' => $organization->id, 'role' => $name])->log('organization_role.deleted');

        return redirect()->route($this->routePrefix().'index', $organization)->with('status', 'role-deleted');
    }

    public function addMember(Request $request, Organization $organization, Role $role): RedirectResponse
    {
        $this->ensureBelongsToOrganization($organization, $role);

        if ($role->name === OrganizationRoleService::ROLE_OWNER) {
            throw ValidationException::withMessages([
                'role' => __('admin.organizations.roles.errors.owner_role_protected'),
            ]);
        }

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $user = User::findOrFail($validated['user_id']);
        $user->assignRole($role);

        activity('organization')->performedOn($user)->causedBy($request->user())
            ->withProperties(['organization_id' => $organization->id, 'role' => $role->name])->log('organization_role.assigned');

        return redirect()->route($this->routePrefix().'show', [$organization, $role])->with('status', 'role-assigned');
    }

    public function removeMember(Request $request, Organization $organization, Role $role, User $user): RedirectResponse
    {
        $this->ensureBelongsToOrganization($organization, $role);

        if ($role->name === OrganizationRoleService::ROLE_OWNER && $role->users()->count() <= 1) {
            throw ValidationException::withMessages([
                'role' => __('admin.organizations.roles.errors.last_owner'),
            ]);
        }

        $user->removeRole($role);

        activity('organization')->performedOn($user)->causedBy($request->user())
            ->withProperties(['organization_id' => $organization->id, 'role' => $role->name])->log('organization_role.removed');

        return back()->with('status', 'role-removed');
    }

    /**
     * A role reaching here belonging to a *different* organization (or to
     * another guard) 404s rather than being mutated under the wrong context.
     */
    private function ensureBelongsToOrganization(Organization $organization, Role $role): void
    {
        abort_unless((int) $role->team_id === $organization->id && $role->guard_name === OrganizationPermissions::GUARD, 404);
    }
}
