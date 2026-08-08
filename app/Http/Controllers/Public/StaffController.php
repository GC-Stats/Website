<?php

/**
 * GC-Stats — Staff page controller
 *
 * Public profile: photo, name, bio, socials, current + former teams and
 * organizations, latest tagged news (News::staff(), see the sidebar in
 * public.staff.index), and the "Experience" tab (declared XP entries —
 * App\Models\StaffAssignment — grouped by tournament, with a per-role
 * summary and per-role sub-pages). No caching layer since traffic on these
 * pages is expected to stay low. Mirrors Public\PlayerController's
 * canonical-slug-redirect pattern.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Public;

use App\Helpers\StaffRoleLabel;
use App\Models\News;
use App\Models\Organization;
use App\Models\Staff;
use App\Models\Team;
use App\Services\StaffAssignmentService;
use App\Services\StaffOrganizationService;
use App\Services\StaffTeamService;
use App\Support\StaffRoleMetadata;
use App\Support\StaffRoles;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StaffController extends Controller
{
    private function redirectToCanonicalSlug(int $id, ?string $slug, string $routeName, array $extraParams = [])
    {
        $handle = Staff::where('id', $id)->value('handle');
        abort_unless($handle !== null, 404);

        $canonical = Str::routeSlug($handle, $id);
        if ($slug !== $canonical) {
            return redirect()->route($routeName, [$id, $canonical, ...$extraParams], 301);
        }

        return null;
    }

    public function index(int $id, ?string $slug = null)
    {
        if ($redirect = $this->redirectToCanonicalSlug($id, $slug, 'staff.show')) {
            return $redirect;
        }

        $staffMember = Staff::with('player.currentTeams')->findOrFail($id);

        $organizationHistory = app(StaffOrganizationService::class)->organizationHistory($staffMember->id);
        $teamHistory = app(StaffTeamService::class)->teamHistory($staffMember->id);

        // teamHistory()/organizationHistory() are raw DB::table() rows, not
        // Eloquent models, so they carry no ->logo accessor — batch-fetch the
        // real models just to borrow their resolved logo URL.
        $teamLogos = Team::whereIn('id', $teamHistory->pluck('team_id'))->get()->keyBy('id');
        $organizationLogos = Organization::whereIn('id', $organizationHistory->pluck('organization_id'))->get()->keyBy('id');

        $teamHistory = $teamHistory->map(fn ($entry) => (object) array_merge((array) $entry, [
            'team_logo' => $teamLogos->get($entry->team_id)?->logo,
        ]));

        $organizationHistory = $organizationHistory->map(fn ($entry) => (object) array_merge((array) $entry, [
            'organization_logo' => $organizationLogos->get($entry->organization_id)?->logo,
        ]));

        $news = News::with(['author', 'organization'])
            ->published()
            ->forLocale(app()->getLocale())
            ->whereHas('staff', fn ($q) => $q->where('staff.id', $id))
            ->latest('published_at')
            ->take(3)
            ->get()
            ->toArray();

        return view('public.staff.index', [
            'staffMember' => $staffMember,
            'currentOrganizations' => $organizationHistory->whereNull('left_at')->values(),
            'formerOrganizations' => $organizationHistory->whereNotNull('left_at')->values(),
            'currentTeams' => $teamHistory->whereNull('left_at')->values(),
            'formerTeams' => $teamHistory->whereNotNull('left_at')->values(),
            'news' => $news,
        ]);
    }

    /**
     * "Experience" tab: every declared XP entry, grouped by tournament (most
     * recent first, via the tournament's own start_date — see
     * StaffAssignment::tournamentStartDate()), with a per-role summary
     * (count + earliest date) linking to experienceByRole() above the list.
     */
    public function experience(int $id, ?string $slug = null)
    {
        if ($redirect = $this->redirectToCanonicalSlug($id, $slug, 'staff.experience')) {
            return $redirect;
        }

        return $this->renderExperience($id, $slug, null);
    }

    /**
     * Same grouped-by-tournament view as experience(), pre-filtered to one
     * role — $role arrives slugified (e.g. "assistant-coach") since raw
     * role values contain spaces; reverse-matched against every known role.
     */
    public function experienceByRole(int $id, ?string $slug, string $role)
    {
        $resolvedRole = collect(StaffRoles::ALL_ROLES)->first(fn ($candidate) => Str::slug($candidate) === $role);
        abort_unless($resolvedRole !== null, 404);

        if ($redirect = $this->redirectToCanonicalSlug($id, $slug, 'staff.experience.role', [$role])) {
            return $redirect;
        }

        return $this->renderExperience($id, $slug, $resolvedRole);
    }

    private function renderExperience(int $id, ?string $slug, ?string $roleFilter)
    {
        $staffMember = Staff::findOrFail($id);

        $assignments = app(StaffAssignmentService::class)->forStaff($id);

        $summary = $assignments
            ->groupBy('role')
            ->map(fn ($group, $role) => [
                'role' => $role,
                'slug' => Str::slug($role),
                'count' => $group->count(),
                'since' => $group->map->tournamentStartDate()->filter()->min(),
            ])
            ->sortBy(fn ($entry) => array_search($entry['role'], StaffRoles::ALL_ROLES, true))
            ->values();

        $filtered = $roleFilter ? $assignments->where('role', $roleFilter) : $assignments;

        $groups = $filtered
            ->groupBy(fn ($assignment) => $assignment->assignable_type === 'tournament'
                ? $assignment->assignable_id
                : $assignment->assignable?->tournament_id)
            ->filter(fn ($group, $tournamentId) => $tournamentId !== null)
            ->map(function ($group) {
                $first = $group->first();
                $tournament = $first->assignable_type === 'tournament' ? $first->assignable : $first->assignable?->tournament;

                return [
                    'tournament' => $tournament,
                    'date' => $first->tournamentStartDate(),
                    'entries' => $group->values(),
                ];
            })
            ->sortByDesc(fn ($group) => $group['date'])
            ->values();

        return view('public.staff.experience', [
            'staffMember' => $staffMember,
            'groups' => $groups,
            'summary' => $summary,
            'roleFilter' => $roleFilter,
            'careerStats' => $roleFilter ? null : $this->careerStats($assignments, $summary),
            'roleStats' => $roleFilter ? $this->roleStats($filtered, $groups, $roleFilter) : null,
        ]);
    }

    /**
     * Top-of-page "career" strip on the unfiltered experience page: distinct
     * tournaments worked across every role, the year it all started, and how
     * many distinct roles have been held — a quick CV-style headline above
     * the per-role tile grid.
     */
    private function careerStats(Collection $assignments, Collection $summary): array
    {
        $tournamentIds = $assignments
            ->map(fn ($assignment) => $assignment->assignable_type === 'tournament'
                ? $assignment->assignable_id
                : $assignment->assignable?->tournament_id)
            ->filter()
            ->unique();

        return [
            'tournaments' => $tournamentIds->count(),
            'roles' => $summary->count(),
            'since' => $summary->pluck('since')->filter()->min(),
        ];
    }

    /**
     * Stats for a single role's experience page — everything derivable from
     * the declared XP entries themselves (no role-specific columns exist),
     * so this stays generic: volume (tournaments vs individual matches),
     * which teams/orgs were represented (with a per-entity count — e.g. a
     * coach's "teams accompanied"), the tournament category breakdown
     * (Championship/Regional/...), and years active. Casters additionally
     * get a cast-language breakdown, the only role carrying structured
     * metadata (see StaffRoleMetadata).
     *
     * @param  Collection<int, \App\Models\StaffAssignment>  $filtered
     * @param  Collection<int, array>  $groups  Same tournament groups built for the list below — reused so category counts don't re-walk the assignable relation.
     */
    private function roleStats(Collection $filtered, Collection $groups, string $roleFilter): array
    {
        $dates = $filtered->map->tournamentStartDate()->filter();

        $categories = $groups
            ->pluck('tournament.category')
            ->filter()
            ->countBy()
            ->sortDesc();

        // Teams (or orgs) represented, deduped, with how many entries were
        // logged for each — the "teams accompanied" list on a coach's page,
        // generalized to every team-/org-scoped role.
        $represented = $filtered
            ->groupBy(fn ($assignment) => $assignment->team_id
                ? 'team:'.$assignment->team_id
                : ($assignment->organization_id ? 'org:'.$assignment->organization_id : null))
            ->forget('')
            ->map(fn ($group) => [
                'entity' => $group->first()->team ?? $group->first()->organization,
                'count' => $group->count(),
            ])
            ->filter(fn ($row) => $row['entity'] !== null)
            ->sortByDesc('count')
            ->values();

        $languages = StaffRoleMetadata::hasMetadata($roleFilter)
            ? $filtered->pluck('metadata.language')->filter()
                ->countBy(fn ($code) => StaffRoleMetadata::LANGUAGES[$code] ?? $code)
                ->sortDesc()
            : collect();

        return [
            'total' => $filtered->count(),
            'tournaments' => $groups->count(),
            'matches' => $filtered->where('assignable_type', 'match')->count(),
            'represented' => $represented,
            'representedIsTeam' => StaffRoleLabel::group($roleFilter) === 'team',
            'categories' => $categories,
            'firstYear' => $dates->min()?->format('Y'),
            'lastYear' => $dates->max()?->format('Y'),
            'languages' => $languages,
        ];
    }
}
