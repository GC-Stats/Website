<?php

/**
 * GC-Stats — Organization dashboard: staff experience (XP)
 *
 * Lets an organization declare XP entries for its own tournaments/matches —
 * for one of its own staff members, or for the organization itself
 * (staff_id null, see StaffAssignment's docblock). index() lists every
 * currently active tournament (Tournament::active) to browse from — no
 * per-org filtering, any org can log XP against any active tournament.
 * tournament()/match() reuse the same bulk roster-style editor pattern as
 * the admin-side event-scoped editor (Admin\TournamentController/
 * Admin\MatchController), but every submitted entry is force-scoped to
 * this organization (organization_id in $scope, merged onto every row by
 * StaffAssignmentService::save()) so a member of one org can never touch
 * another org's — or the site admin's own — entries for the same event.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Public\Controller;
use App\Models\Matchs;
use App\Models\Organization;
use App\Models\StaffAssignment;
use App\Models\Tournament;
use App\Services\OrganizationAccessService;
use App\Services\StaffAssignmentService;
use App\Support\StaffRoleMetadata;
use App\Support\StaffRoles;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExperienceController extends Controller
{
    private const SORTABLE = ['name', 'start_date', 'region', 'status'];

    public function __construct(private readonly OrganizationAccessService $access) {}

    public function index(Request $request, Organization $organization): View
    {
        abort_unless($this->access->canManageExperience($request->user(), $organization), 403);

        $search = $request->get('q');
        $region = $request->get('region');
        $status = $request->get('status');
        $sort = $request->query('sort', 'start_date');
        if (! in_array($sort, self::SORTABLE, true)) {
            $sort = 'start_date';
        }
        $direction = $sort === 'name' ? 'asc' : 'desc';

        $tournaments = Tournament::where('active', true)
            ->withCount('teams')
            ->when($search, fn ($query) => $query->where('name', 'like', '%'.$this->escapeLike($search).'%'))
            ->when($region, fn ($query) => $query->where('region', $region))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderBy($sort, $direction)
            ->paginate(25)
            ->withQueryString();

        return view('organization.dashboard.experience.index', [
            'organization' => $organization,
            'tournaments' => $tournaments,
            'search' => $search ?? '',
            'region' => $region ?? '',
            'status' => $status ?? '',
            'sort' => $sort,
            'regions' => array_keys(config('regions.riot_api')),
        ]);
    }

    public function tournament(Request $request, Organization $organization, Tournament $tournament): View
    {
        abort_unless($this->access->canManageExperience($request->user(), $organization), 403);

        $entries = StaffAssignment::where('assignable_type', 'tournament')
            ->where('assignable_id', $tournament->id)
            ->where('organization_id', $organization->id)
            ->with('staff')
            ->orderByDesc('id')
            ->get();

        $matches = $tournament->matches()->with(['teamA', 'teamB'])->orderByDesc('scheduled_at')->get();

        return view('organization.dashboard.experience.tournament', [
            'organization' => $organization,
            'tournament' => $tournament,
            'matches' => $matches,
            'entries' => $entries,
        ]);
    }

    public function syncTournament(Request $request, Organization $organization, Tournament $tournament, StaffAssignmentService $staffAssignments): RedirectResponse
    {
        abort_unless($this->access->canManageExperience($request->user(), $organization), 403);

        $validated = $request->validate([
            'entries' => ['array'],
            'entries.*.id' => ['nullable', 'integer', Rule::exists('staff_assignments', 'id')
                ->where('assignable_type', 'tournament')
                ->where('assignable_id', $tournament->id)
                ->where('organization_id', $organization->id)],
            'entries.*.staff_id' => ['nullable', 'integer', 'exists:staff,id'],
            'entries.*.role' => ['required', 'string', Rule::in(StaffRoles::ORG_ROLES)],
            'entries.*.metadata' => ['nullable', 'array'],
            'entries.*.metadata.language' => ['nullable', 'string', Rule::in(array_keys(StaffRoleMetadata::LANGUAGES))],
        ]);

        $entries = collect($validated['entries'] ?? [])->all();

        $staffAssignments->save([
            'assignable_type' => 'tournament',
            'assignable_id' => $tournament->id,
            'organization_id' => $organization->id,
        ], $entries);

        activity('organization')->performedOn($tournament)->causedBy($request->user())
            ->withProperties(['organization_id' => $organization->id, 'tournament_id' => $tournament->id])
            ->log('organization.experience.synced');

        return back()->with('status', 'staff-experience-synced');
    }

    public function match(Request $request, Organization $organization, Tournament $tournament, Matchs $match): View
    {
        abort_unless($this->access->canManageExperience($request->user(), $organization), 403);
        abort_unless($match->tournament_id === $tournament->id, 404);

        $match->load(['teamA', 'teamB']);

        $entries = StaffAssignment::where('assignable_type', 'match')
            ->where('assignable_id', $match->id)
            ->where('organization_id', $organization->id)
            ->with('staff')
            ->orderByDesc('id')
            ->get();

        return view('organization.dashboard.experience.match', [
            'organization' => $organization,
            'tournament' => $tournament,
            'match' => $match,
            'entries' => $entries,
        ]);
    }

    public function syncMatch(Request $request, Organization $organization, Tournament $tournament, Matchs $match, StaffAssignmentService $staffAssignments): RedirectResponse
    {
        abort_unless($this->access->canManageExperience($request->user(), $organization), 403);
        abort_unless($match->tournament_id === $tournament->id, 404);

        $validated = $request->validate([
            'entries' => ['array'],
            'entries.*.id' => ['nullable', 'integer', Rule::exists('staff_assignments', 'id')
                ->where('assignable_type', 'match')
                ->where('assignable_id', $match->id)
                ->where('organization_id', $organization->id)],
            'entries.*.staff_id' => ['nullable', 'integer', 'exists:staff,id'],
            'entries.*.role' => ['required', 'string', Rule::in(StaffRoles::ORG_ROLES)],
            'entries.*.metadata' => ['nullable', 'array'],
            'entries.*.metadata.language' => ['nullable', 'string', Rule::in(array_keys(StaffRoleMetadata::LANGUAGES))],
        ]);

        $entries = collect($validated['entries'] ?? [])->all();

        $staffAssignments->save([
            'assignable_type' => 'match',
            'assignable_id' => $match->id,
            'organization_id' => $organization->id,
        ], $entries);

        activity('organization')->performedOn($match)->causedBy($request->user())
            ->withProperties(['organization_id' => $organization->id, 'match_id' => $match->id])
            ->log('organization.experience.synced');

        return back()->with('status', 'staff-experience-synced');
    }
}
