<?php

/**
 * GC-Stats — Admin: staff
 *
 * Profile/logo editing and deletion for staff members (coach, analyst,
 * manager, caster, observer, etc.) — the counterpart to Admin\PlayerController
 * for non-player roles. Deliberately simpler than Player: no external
 * identifiers (val_id/discord_id) or merge tooling yet. Gated by
 * `staff.view`/`staff.edit`/`staff.delete`.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ValidatesStaffExperienceRoles;
use App\Http\Controllers\Public\Controller;
use App\Models\Organization;
use App\Models\Player;
use App\Models\Staff;
use App\Models\User;
use App\Services\HtmlSanitizer;
use App\Services\LogoUploadService;
use App\Services\StaffAssignmentService;
use App\Services\StaffOrganizationService;
use App\Services\StaffTeamService;
use App\Support\Activity\ActivityChangeSet;
use App\Support\Countries;
use App\Support\Pronouns;
use App\Support\StaffRoleMetadata;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    use ValidatesStaffExperienceRoles;

    public function index(Request $request): View
    {
        $search = $request->get('q');
        $sort = $request->get('sort', 'name');

        $staff = Staff::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('handle', 'like', '%'.$this->escapeLike($search).'%');

                    if (ctype_digit($search)) {
                        $query->orWhere('id', (int) $search);
                    }
                });
            })
            ->when($sort === 'country', fn ($query) => $query->orderBy('country_code'))
            ->when($sort === 'name', fn ($query) => $query->orderBy('handle'))
            ->paginate(25)
            ->withQueryString();

        return view('admin.staff.index', [
            'staff' => $staff,
            'search' => $search ?? '',
            'sort' => $sort,
            'countries' => app(Countries::class)->list(),
            'organizationOptions' => Organization::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Quick creation from the admin staff list — only the handle is
     * required. If an organization is picked, the staff member joins its
     * roster via StaffOrganizationService (no auto-close, unlike RosterService,
     * since a staff member may already sit on other active rosters).
     */
    public function store(Request $request, StaffOrganizationService $staffOrganizations): RedirectResponse
    {
        $validated = $request->validate([
            'handle' => ['required', 'string', 'max:255'],
            'country_code' => ['nullable', 'string', 'max:5'],
            'vlr_id' => ['nullable', 'integer'],
            'organization_id' => ['nullable', 'integer', 'exists:organization,id'],
        ]);

        $staffMember = Staff::create([
            'handle' => $validated['handle'],
            'country_code' => $validated['country_code'] ?? null,
            'vlr_id' => $validated['vlr_id'] ?? null,
            'is_active' => true,
        ]);

        if (! empty($validated['organization_id'])) {
            $staffOrganizations->addMember(Organization::findOrFail($validated['organization_id']), $staffMember->id, null, now()->toDateString());
        }

        activity('staff')->performedOn($staffMember)->causedBy($request->user())
            ->withProperties(ActivityChangeSet::fromCreated($staffMember, ['handle', 'country_code', 'vlr_id', 'is_active'])->toArray())
            ->log('staff.created');

        return redirect()->route('admin.staff.index')->with('status', 'staff-created')->with('created_staff', $staffMember->id);
    }

    public function show(Request $request, Staff $staffMember, StaffOrganizationService $staffOrganizations, StaffTeamService $staffTeams): View
    {
        $userSearch = $request->get('user_q');
        $linkedUserIds = Staff::whereNotNull('user_id')->where('id', '!=', $staffMember->id)->pluck('user_id');

        $playerSearch = $request->get('player_q');
        $linkedPlayerIds = Staff::whereNotNull('player_id')->where('id', '!=', $staffMember->id)->pluck('player_id');

        $organizationHistory = $staffOrganizations->organizationHistory($staffMember->id);
        $teamHistory = $staffTeams->teamHistory($staffMember->id);

        return view('admin.staff.show', [
            'staffMember' => $staffMember,
            'countries' => app(Countries::class)->list(),
            'pronounOptions' => Pronouns::OPTIONS,
            'userSearch' => $userSearch ?? '',
            'userSearchResults' => $userSearch
                ? User::matching($userSearch)->whereNotIn('id', $linkedUserIds)->limit(10)->get()
                : collect(),
            'playerSearch' => $playerSearch ?? '',
            'playerSearchResults' => $playerSearch
                ? Player::whereNotIn('id', $linkedPlayerIds)->where('handle', 'like', '%'.$this->escapeLike($playerSearch).'%')->limit(10)->get()
                : collect(),
            'currentOrganizations' => $organizationHistory->whereNull('left_at')->values(),
            'organizationHistory' => $organizationHistory->whereNotNull('left_at')->values(),
            'currentTeams' => $teamHistory->whereNull('left_at')->values(),
            'teamHistory' => $teamHistory->whereNotNull('left_at')->values(),
            'experienceEntries' => app(StaffAssignmentService::class)->forStaff($staffMember->id),
        ]);
    }

    public function syncExperience(Request $request, Staff $staffMember, StaffAssignmentService $staffAssignments): RedirectResponse
    {
        $validated = $request->validate([
            'entries' => ['array'],
            'entries.*.id' => ['nullable', 'integer', Rule::exists('staff_assignments', 'id')->where('staff_id', $staffMember->id)],
            'entries.*.tournament_id' => ['required', 'integer', 'exists:tournaments,id'],
            'entries.*.match_id' => ['nullable', 'integer', 'exists:matches,id'],
            'entries.*.team_id' => ['nullable', 'integer', 'exists:teams,id', 'required_without:entries.*.organization_id'],
            'entries.*.organization_id' => ['nullable', 'integer', 'exists:organization,id', 'required_without:entries.*.team_id'],
            'entries.*.role' => ['required', 'string', $this->roleMatchesRepresentedEntity($request)],
            'entries.*.metadata' => ['nullable', 'array'],
            'entries.*.metadata.language' => ['nullable', 'string', Rule::in(array_keys(StaffRoleMetadata::LANGUAGES))],
        ]);

        $entries = collect($validated['entries'] ?? [])
            ->map(fn (array $entry) => [
                ...$entry,
                'assignable_type' => $entry['match_id'] ?? null ? 'match' : 'tournament',
                'assignable_id' => $entry['match_id'] ?? $entry['tournament_id'],
            ])
            ->all();

        $staffAssignments->save(['staff_id' => $staffMember->id], $entries);

        activity('staff')->performedOn($staffMember)->causedBy($request->user())
            ->withProperties(['staff_id' => $staffMember->id])
            ->log('staff.experience.synced');

        return back()->with('status', 'staff-experience-synced');
    }

    public function syncOrganizationHistory(Request $request, Staff $staffMember, StaffOrganizationService $staffOrganizations): RedirectResponse
    {
        $validated = $request->validate([
            'entries' => ['array'],
            'entries.*.id' => ['nullable', 'integer', Rule::exists('staff_organizations', 'id')->where('staff_id', $staffMember->id)],
            'entries.*.organization_id' => ['required', 'integer', 'exists:organization,id'],
            'entries.*.role' => ['nullable', 'string', Rule::in(StaffOrganizationService::ROLES)],
            'entries.*.joined_at' => ['required', 'date'],
            'entries.*.left_at' => ['nullable', 'date'],
        ]);

        $entries = collect($validated['entries'] ?? [])
            ->map(fn (array $entry) => [...$entry, 'staff_id' => $staffMember->id])
            ->all();

        $staffOrganizations->save('staff_id', $staffMember->id, $entries);

        activity('staff')->performedOn($staffMember)->causedBy($request->user())
            ->withProperties(['staff_id' => $staffMember->id])->log('staff.organization_history.synced');

        return back()->with('status', 'organization-history-synced');
    }

    public function syncTeamHistory(Request $request, Staff $staffMember, StaffTeamService $staffTeams): RedirectResponse
    {
        $validated = $request->validate([
            'entries' => ['array'],
            'entries.*.id' => ['nullable', 'integer', Rule::exists('staff_teams', 'id')->where('staff_id', $staffMember->id)],
            'entries.*.team_id' => ['required', 'integer', 'exists:teams,id'],
            'entries.*.role' => ['nullable', 'string', Rule::in(StaffTeamService::ROLES)],
            'entries.*.joined_at' => ['required', 'date'],
            'entries.*.left_at' => ['nullable', 'date'],
        ]);

        $entries = collect($validated['entries'] ?? [])
            ->map(fn (array $entry) => [...$entry, 'staff_id' => $staffMember->id])
            ->all();

        $staffTeams->save('staff_id', $staffMember->id, $entries);

        activity('staff')->performedOn($staffMember)->causedBy($request->user())
            ->withProperties(['staff_id' => $staffMember->id])->log('staff.team_history.synced');

        return back()->with('status', 'staff-teams-synced');
    }

    public function linkUser(Request $request, Staff $staffMember): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id', Rule::unique('staff', 'user_id')->ignore($staffMember->id)],
        ]);

        $staffMember->update(['user_id' => $validated['user_id']]);

        activity('staff')->performedOn($staffMember)->causedBy($request->user())
            ->withProperties(['user_id' => $validated['user_id']])->log('staff.user_linked');

        return back()->with('status', 'user-linked');
    }

    public function unlinkUser(Request $request, Staff $staffMember): RedirectResponse
    {
        $staffMember->update(['user_id' => null]);

        activity('staff')->performedOn($staffMember)->causedBy($request->user())
            ->log('staff.user_unlinked');

        return back()->with('status', 'user-unlinked');
    }

    public function linkPlayer(Request $request, Staff $staffMember): RedirectResponse
    {
        $validated = $request->validate([
            'player_id' => ['required', 'integer', 'exists:players,id', Rule::unique('staff', 'player_id')->ignore($staffMember->id)],
        ]);

        $staffMember->update(['player_id' => $validated['player_id']]);

        Player::find($validated['player_id'])?->touch();

        activity('staff')->performedOn($staffMember)->causedBy($request->user())
            ->withProperties(['player_id' => $validated['player_id']])->log('staff.player_linked');

        return back()->with('status', 'player-linked');
    }

    public function unlinkPlayer(Request $request, Staff $staffMember): RedirectResponse
    {
        $previousPlayerId = $staffMember->player_id;

        $staffMember->update(['player_id' => null]);

        if ($previousPlayerId) {
            Player::find($previousPlayerId)?->touch();
        }

        activity('staff')->performedOn($staffMember)->causedBy($request->user())
            ->log('staff.player_unlinked');

        return back()->with('status', 'player-unlinked');
    }

    public function updateProfile(Request $request, Staff $staffMember): RedirectResponse
    {
        $validated = $request->validate([
            'handle' => ['required', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'country_code' => ['nullable', 'string', 'max:5'],
            'pronouns' => ['required', 'integer', Rule::in(Pronouns::OPTIONS)],
            'bio' => ['nullable', 'string', 'max:2000'],
            'vlr_id' => ['nullable', 'integer'],
            'liquipedia_link' => ['nullable', 'url', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'socials' => ['nullable', 'array'],
            'socials.*' => ['nullable', 'string', 'max:255', function ($attribute, $value, $fail) {
                if (! HtmlSanitizer::isSafeUrl($value)) {
                    $fail('The '.$attribute.' field must be a valid link.');
                }
            }],
        ]);

        $validated['is_active'] = $validated['is_active'] ?? false;
        $validated['socials'] = array_filter($validated['socials'] ?? [], fn ($value) => filled($value));

        $staffMember->update($validated);

        activity('staff')->performedOn($staffMember)->causedBy($request->user())
            ->withProperties(ActivityChangeSet::fromModel($staffMember, array_keys($validated))->toArray())
            ->log('staff.profile_updated');

        return back()->with('status', 'profile-updated');
    }

    public function updateLogo(Request $request, Staff $staffMember, LogoUploadService $logoUploadService): RedirectResponse
    {
        $validated = $request->validate(['logo' => ['required', 'file', 'image', 'max:10240']]);

        $oldLogoId = $staffMember->logos->pluck('id')->first();

        $uuid = $logoUploadService->storeLogoPair($validated['logo'], 'staff');
        $logoUploadService->acceptReplacing($staffMember, 'staff', $uuid, 'staff');

        activity('staff')->performedOn($staffMember)->causedBy($request->user())
            ->withProperties(['changes' => ['logo_id' => ['old' => $oldLogoId, 'new' => $uuid]]])
            ->log('staff.logo_updated');

        return back()->with('status', 'logo-updated');
    }

    public function destroy(Request $request, Staff $staffMember): RedirectResponse
    {
        $handle = $staffMember->handle;
        $staffMember->delete();

        activity('staff')->causedBy($request->user())
            ->withProperties(['handle' => $handle])
            ->log('staff.deleted');

        return redirect()->route('admin.staff.index')->with('status', 'staff-deleted');
    }
}
