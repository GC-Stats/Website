<?php

/**
 * GC-Stats — Admin: organizations
 *
 * Site admins see and manage every organization; an organization's own
 * owner/editor (granted via App\Services\OrganizationRoleService, guard
 * 'organization' — see App\Support\OrganizationPermissions) reaches the same
 * `show` page for their own organization and edits within their permission
 * ceiling. Assigning an owner and setting max_permissions is site-admin
 * only.
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
use App\Services\LogoUploadService;
use App\Services\OrganizationAccessService;
use App\Services\OrganizationProfileService;
use App\Services\OrganizationRoleService;
use App\Services\StaffOrganizationService;
use App\Support\Activity\ActivityChangeSet;
use App\Support\Countries;
use App\Support\OrganizationPermissions;
use App\Support\OrganizationScope;
use App\Support\PermissionTeam;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class OrganizationController extends Controller
{
    public function __construct(
        private readonly OrganizationAccessService $access,
        private readonly OrganizationProfileService $profile,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        if (! $request->user()->can('organizations.view')) {
            $organizationId = OrganizationScope::organizationIdsForUser($request->user()->id)->first();

            abort_unless($organizationId, 403);

            return redirect()->route('admin.organizations.show', $organizationId);
        }

        $search = $request->get('q');

        [$sort, $direction] = $this->resolveSort($request, ['name', 'count'], 'name', 'asc');

        $organizations = Organization::query()
            ->withCount('staff')
            ->when($search, fn ($query) => $query->where('name', 'like', '%'.$this->escapeLike($search).'%'))
            ->when($sort === 'count', fn ($query) => $query->orderBy('staff_count', $direction))
            ->when($sort === 'name', fn ($query) => $query->orderBy('name', $direction))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.organizations.index', [
            'organizations' => $organizations,
            'search' => $search ?? '',
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function show(Request $request, Organization $organization, OrganizationRoleService $organizationRoles, StaffOrganizationService $staffOrganizations): View
    {
        abort_unless($this->access->canView($request->user(), $organization), 403);

        $organizationRoles->ensureRolesExist($organization);

        PermissionTeam::use($organization->id);
        $ownerRole = Role::where('team_id', $organization->id)
            ->where('guard_name', OrganizationPermissions::GUARD)
            ->where('name', OrganizationRoleService::ROLE_OWNER)->first();
        $owners = $ownerRole?->users()->orderBy('name')->get() ?? collect();
        PermissionTeam::global();

        $search = $request->get('q');
        $existingOwnerIds = $ownerRole
            ? $ownerRole->users()->pluck('users.id')
            : collect();

        $staffHistory = $staffOrganizations->history($organization->id);

        return view('admin.organizations.show', [
            'organization' => $organization,
            'owners' => $owners,
            'permissionGroups' => OrganizationPermissions::grouped(),
            'search' => $search ?? '',
            'searchResults' => $search
                ? User::matching($search)->whereNotIn('id', $existingOwnerIds)->limit(10)->get()
                : collect(),
            'canEditProfile' => $this->access->canEditProfile($request->user(), $organization),
            'canUploadLogo' => $this->access->canUploadLogo($request->user(), $organization),
            'canManageStaff' => $this->access->canManageStaff($request->user(), $organization),
            'countries' => app(Countries::class)->list(),
            'currentStaff' => $staffHistory->whereNull('left_at')->values(),
            'staffHistory' => $staffHistory->whereNotNull('left_at')->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->profile->validate($request, null);

        $organization = Organization::create($validated);

        activity('organization')->performedOn($organization)->causedBy($request->user())
            ->withProperties(ActivityChangeSet::fromCreated($organization, array_keys($validated))->toArray())
            ->log('organization.created');

        return redirect()->route('admin.organizations.show', $organization)->with('status', 'organization-created');
    }

    public function update(Request $request, Organization $organization): RedirectResponse
    {
        abort_unless($this->access->canEditProfile($request->user(), $organization), 403);

        $validated = $this->profile->validate($request, $organization);

        $organization->update($validated);

        activity('organization')->performedOn($organization)->causedBy($request->user())
            ->withProperties(ActivityChangeSet::fromModel($organization, array_keys($validated))->toArray())
            ->log('organization.information_updated');

        return back()->with('status', 'organization-updated');
    }

    public function updateLogo(Request $request, Organization $organization, LogoUploadService $logoUploadService): RedirectResponse
    {
        abort_unless($this->access->canUploadLogo($request->user(), $organization), 403);

        $validated = $request->validate(['logo' => ['required', 'file', 'image', 'max:10240']]);

        $oldLogoId = $organization->logos->pluck('id')->first();

        $uuid = $logoUploadService->storeLogoPair($validated['logo'], 'organizations');
        $logoUploadService->acceptReplacing($organization, 'organization', $uuid, 'organizations');

        activity('organization')->performedOn($organization)->causedBy($request->user())
            ->withProperties(['changes' => ['logo_id' => ['old' => $oldLogoId, 'new' => $uuid]]])
            ->log('organization.logo_updated');

        return back()->with('status', 'logo-updated');
    }

    public function updateMaxPermissions(Request $request, Organization $organization): RedirectResponse
    {
        $validated = $request->validate([
            'max_permissions' => ['array'],
            'max_permissions.*' => ['string', Rule::in(OrganizationPermissions::all())],
        ]);

        $ceiling = $validated['max_permissions'] ?? [];
        $organization->update(['max_permissions' => $ceiling]);

        PermissionTeam::use($organization->id);
        foreach (Role::where('team_id', $organization->id)->where('guard_name', OrganizationPermissions::GUARD)->get() as $role) {
            $permissions = $role->name === OrganizationRoleService::ROLE_OWNER
                ? $ceiling
                : array_intersect($role->permissions->pluck('name')->all(), $ceiling);

            $role->syncPermissions($permissions);
        }
        PermissionTeam::global();

        activity('organization')->performedOn($organization)->causedBy($request->user())
            ->withProperties(ActivityChangeSet::fromModel($organization, ['max_permissions'])->toArray())
            ->log('organization.max_permissions_updated');

        return back()->with('status', 'max-permissions-updated');
    }

    public function assignOwner(Request $request, Organization $organization, OrganizationRoleService $organizationRoles): RedirectResponse
    {
        $validated = $request->validate(['user_id' => ['required', 'integer', 'exists:users,id']]);

        $user = User::findOrFail($validated['user_id']);
        $organizationRoles->assign($user, $organization, OrganizationRoleService::ROLE_OWNER);

        activity('organization')->performedOn($user)->causedBy($request->user())
            ->withProperties(['organization_id' => $organization->id])
            ->log('organization.owner_assigned');

        return redirect()->route('admin.organizations.show', $organization)->with('status', 'owner-assigned');
    }

    public function removeOwner(Request $request, Organization $organization, User $user, OrganizationRoleService $organizationRoles): RedirectResponse
    {
        $organizationRoles->revoke($user, $organization, OrganizationRoleService::ROLE_OWNER);

        activity('organization')->performedOn($user)->causedBy($request->user())
            ->withProperties(['organization_id' => $organization->id])
            ->log('organization.owner_removed');

        return back()->with('status', 'owner-removed');
    }

    public function syncStaff(Request $request, Organization $organization, StaffOrganizationService $staffOrganizations): RedirectResponse
    {
        abort_unless($this->access->canManageStaff($request->user(), $organization), 403);

        $validated = $request->validate([
            'entries' => ['array'],
            'entries.*.id' => ['nullable', 'integer', Rule::exists('staff_organizations', 'id')->where('organization_id', $organization->id)],
            'entries.*.staff_id' => ['required', 'integer', 'exists:staff,id'],
            'entries.*.role' => ['nullable', 'string', Rule::in(StaffOrganizationService::ROLES)],
            'entries.*.joined_at' => ['required', 'date'],
            'entries.*.left_at' => ['nullable', 'date'],
        ]);

        $entries = collect($validated['entries'] ?? [])
            ->map(fn (array $entry) => [...$entry, 'organization_id' => $organization->id])
            ->all();

        $staffOrganizations->save('organization_id', $organization->id, $entries);

        activity('organization')->performedOn($organization)->causedBy($request->user())
            ->withProperties(['organization_id' => $organization->id])->log('organization.staff_synced');

        return back()->with('status', 'staff-synced');
    }

    public function destroy(Request $request, Organization $organization): RedirectResponse
    {
        $name = $organization->name;
        $organization->delete();

        activity('organization')->causedBy($request->user())
            ->withProperties(['name' => $name])
            ->log('organization.deleted');

        return redirect()->route('admin.organizations.index')->with('status', 'organization-deleted');
    }
}
