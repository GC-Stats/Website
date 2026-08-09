<?php

/**
 * GC-Stats — Organization dashboard
 *
 * The organization's own dedicated space — same design/principle as /admin
 * (see resources/views/organization/layout.blade.php, modeled on
 * admin.layout/developers.layout), but scoped to a single organization and
 * reachable by anyone holding a role on it, not just site admins. Reuses
 * the exact same access rules and profile validation as
 * Admin\OrganizationController (see App\Services\OrganizationAccessService/
 * OrganizationProfileService) so the two surfaces can never drift apart on
 * what counts as "can edit". Owner assignment and the max_permissions
 * ceiling stay site-admin only — not exposed here.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Public\Controller;
use App\Models\Organization;
use App\Services\LogoUploadService;
use App\Services\OrganizationAccessService;
use App\Services\OrganizationProfileService;
use App\Services\StaffOrganizationService;
use App\Support\Activity\ActivityChangeSet;
use App\Support\Countries;
use App\Support\OrganizationPermissions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function __construct(
        private readonly OrganizationAccessService $access,
        private readonly OrganizationProfileService $profile,
    ) {}

    /**
     * Read-only summary: profile snapshot + a few headline counts. Editing
     * lives on its own page (edit()) — see this class's docblock for why
     * the two are split.
     */
    public function index(Request $request, Organization $organization, StaffOrganizationService $staffOrganizations): View
    {
        abort_unless($this->access->canView($request->user(), $organization), 403);

        $staffHistory = $staffOrganizations->history($organization->id);
        $currentStaff = $staffHistory->whereNull('left_at')->values();

        return view('organization.dashboard.index', [
            'organization' => $organization,
            'canEdit' => $this->canEditAnything($request, $organization),
            'currentStaffCount' => $currentStaff->count(),
            'formerStaffCount' => $staffHistory->whereNotNull('left_at')->count(),
            'rolesCount' => Role::where('team_id', $organization->id)->where('guard_name', OrganizationPermissions::GUARD)->count(),
            'currentStaff' => $currentStaff->take(5),
        ]);
    }

    /**
     * The editable form page (profile, logo, staff roster) — split out from
     * index() so the overview stays a clean, always-visible summary
     * regardless of what the current user can actually edit.
     */
    public function edit(Request $request, Organization $organization, StaffOrganizationService $staffOrganizations): View
    {
        abort_unless($this->canEditAnything($request, $organization), 403);

        $staffHistory = $staffOrganizations->history($organization->id);

        return view('organization.dashboard.edit', [
            'organization' => $organization,
            'countries' => app(Countries::class)->list(),
            'canEditProfile' => $this->access->canEditProfile($request->user(), $organization),
            'canUploadLogo' => $this->access->canUploadLogo($request->user(), $organization),
            'canManageStaff' => $this->access->canManageStaff($request->user(), $organization),
            'currentStaff' => $staffHistory->whereNull('left_at')->values(),
            'staffHistory' => $staffHistory->whereNotNull('left_at')->values(),
        ]);
    }

    private function canEditAnything(Request $request, Organization $organization): bool
    {
        return $this->access->canEditProfile($request->user(), $organization)
            || $this->access->canUploadLogo($request->user(), $organization)
            || $this->access->canManageStaff($request->user(), $organization);
    }

    public function updateProfile(Request $request, Organization $organization): RedirectResponse
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
}
