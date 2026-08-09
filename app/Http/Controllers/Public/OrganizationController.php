<?php

/**
 * GC-Stats — Organization page controller
 *
 * Public profile: logo, name, types, socials, current + former staff. The
 * News tab lives on its own route (news.organization, Public\NewsController)
 * rather than this controller — the header's tab nav links there.
 * Deliberately simpler than Public\TeamController for now — no tournament/
 * match participation yet (see the "branchement" roadmap step), no caching
 * layer since traffic on these pages is expected to stay low until that
 * lands. Mirrors Public\TeamController's canonical-slug-redirect pattern.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Public;

use App\Models\Matchs;
use App\Models\Organization;
use App\Models\StaffAssignment;
use App\Services\OrganizationAccessService;
use App\Services\StaffOrganizationService;
use Illuminate\Support\Str;

class OrganizationController extends Controller
{
    private function redirectToCanonicalSlug(int $id, ?string $slug, string $routeName)
    {
        $name = Organization::where('id', $id)->value('name');
        abort_unless($name !== null, 404);

        $canonical = Str::routeSlug($name, $id);
        if ($slug !== $canonical) {
            return redirect()->route($routeName, [$id, $canonical], 301);
        }

        return null;
    }

    public function index(int $id, ?string $slug = null)
    {
        if ($redirect = $this->redirectToCanonicalSlug($id, $slug, 'organizations.show')) {
            return $redirect;
        }

        $organization = Organization::findOrFail($id);

        $history = app(StaffOrganizationService::class)->history($organization->id);

        return view('public.organization.index', [
            'organization' => $organization,
            'currentStaff' => $history->whereNull('left_at')->values(),
            'formerStaff' => $history->whereNotNull('left_at')->values(),
            'canManage' => auth()->check() && app(OrganizationAccessService::class)->canView(auth()->user(), $organization),
        ]);
    }

    /**
     * "Experience" tab: XP entries the organization itself holds (staff_id
     * null — declarations like "organized this tournament", not an
     * individual staff member's personal XP), grouped by tournament via the
     * same derived-date logic as Public\StaffController's experience tab.
     */
    public function experience(int $id, ?string $slug = null)
    {
        if ($redirect = $this->redirectToCanonicalSlug($id, $slug, 'organizations.experience')) {
            return $redirect;
        }

        $organization = Organization::findOrFail($id);

        $entries = StaffAssignment::where('organization_id', $id)
            ->whereNull('staff_id')
            ->whereIn('assignable_type', StaffAssignment::ASSIGNABLE_TYPES)
            ->with(['assignable' => fn ($morphTo) => $morphTo->morphWith([Matchs::class => ['tournament', 'teamA', 'teamB']])])
            ->get();

        $groups = $entries
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

        return view('public.organization.experience', [
            'organization' => $organization,
            'groups' => $groups,
        ]);
    }
}
