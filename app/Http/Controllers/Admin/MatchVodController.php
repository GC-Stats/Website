<?php

/**
 * GC-Stats — Admin: match VODs
 *
 * Creates/removes a Vod row directly on a match (and, optionally, one of
 * its maps) — see App\Models\Vod's docblock for why this has no separate
 * "channel" CRUD unlike streams: a VOD is a one-off link, not a reusable
 * entity.
 *
 * Gated by its own permission pair — vods.matches.link / organization.vods.link
 * — mirroring Admin\MatchStreamController, including the same "not gated by
 * matches.view" reasoning: an organization's own member has no access to the
 * admin match list/show pages at all, so the "add a VOD" form lives on the
 * public match page too (see resources/views/match.blade.php) and posts
 * here directly.
 *
 * index()/create() are additionally dual-context like Admin\NewsController
 * — reachable at both admin.vods.* (flat, cross-organization) and
 * organization-dashboard.vods.* (scoped to the {organization} bound in the
 * dashboard URL). store()/update()/destroy() don't need the same
 * treatment — see Admin\MatchStreamController's docblock for why.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\SearchesMatchesForLinking;
use App\Http\Controllers\Public\Controller;
use App\Models\Matchs;
use App\Models\Organization;
use App\Models\Tournament;
use App\Models\Vod;
use App\Services\OrganizationAccessService;
use App\Support\Activity\ActivityChangeSet;
use App\Support\Countries;
use App\Support\OrganizationScope;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class MatchVodController extends Controller
{
    use SearchesMatchesForLinking;

    /** @see SearchesMatchesForLinking */
    private const LINK_PERMISSION = 'vods.matches.link';

    private const SORTABLE = ['scheduled_at', 'tournament'];

    private function isDashboard(): bool
    {
        return request()->routeIs('organization-dashboard.*');
    }

    private function routePrefix(): string
    {
        return $this->isDashboard() ? 'organization-dashboard.vods.' : 'admin.vods.';
    }

    private function viewName(string $page): string
    {
        return $this->isDashboard() ? "organization.dashboard.vods.matches.{$page}" : "admin.vods.matches.{$page}";
    }

    /**
     * "Liste tout" — every match that currently has at least one VOD,
     * grouped by match. Restricted to VODs the current user can see when
     * they're an organization-scoped editor rather than a full site editor
     * (or, in dashboard mode, to that one organization specifically).
     */
    public function index(Request $request, ?Organization $organization = null): View
    {
        $allowedOrganizationIds = $this->allowedOrganizationIds($request, $organization);

        abort_if($allowedOrganizationIds !== null && $allowedOrganizationIds->isEmpty(), 403);

        [$sort, $direction] = $this->resolveSort($request, self::SORTABLE, 'scheduled_at', 'desc');

        $matches = Matchs::query()
            ->select('matches.*')
            ->whereHas('vods', fn ($query) => $this->scopeToAllowedOrganizations($query, $allowedOrganizationIds))
            ->with([
                'teamA:id,name,short_name', 'teamB:id,name,short_name', 'tournament:id,name',
                'tournamentPhase.parent.parent.parent.parent',
                'vods' => fn ($query) => $this->scopeToAllowedOrganizations($query, $allowedOrganizationIds)->with(['organization', 'gameMap']),
                'game_maps' => fn ($query) => $query->orderBy('order'),
            ])
            ->when($sort === 'tournament', fn ($query) => $query
                ->leftJoin('tournaments', 'tournaments.id', '=', 'matches.tournament_id')
                ->orderBy('tournaments.name', $direction))
            ->when($sort === 'scheduled_at', fn ($query) => $query->orderBy('matches.scheduled_at', $direction))
            ->paginate(25)
            ->withQueryString();

        return view($this->viewName('index'), [
            'matches' => $matches,
            'sort' => $sort,
            'direction' => $direction,
            'organization' => $organization,
            'countries' => app(Countries::class)->list(),
            'vodsRestricted' => $allowedOrganizationIds !== null,
            'vodOrganizations' => Organization::query()
                ->when($allowedOrganizationIds !== null, fn ($query) => $query->whereIn('id', $allowedOrganizationIds))
                ->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request, ?Organization $organization = null): View
    {
        $allowedOrganizationIds = $this->allowedOrganizationIds($request, $organization);

        abort_if($allowedOrganizationIds !== null && $allowedOrganizationIds->isEmpty(), 403);

        return view($this->viewName('create'), ['organization' => $organization, 'countries' => app(Countries::class)->list()]);
    }

    /**
     * @param  Collection<int, int>|null  $allowedOrganizationIds
     */
    private function scopeToAllowedOrganizations($query, ?Collection $allowedOrganizationIds)
    {
        return $allowedOrganizationIds === null ? $query : $query->whereIn('organization_id', $allowedOrganizationIds);
    }

    /**
     * $tournament is unused but required: Laravel's implicit route-model
     * binding splices resolved parameters positionally, and the route also
     * carries a {tournament} segment ahead of {match} — dropping it from
     * the signature misaligns the splice and $match ends up receiving the
     * raw {tournament} string instead of the bound Matchs model. Every
     * other method in the sibling Admin\MatchController (and
     * Admin\MatchStreamController) keeps its $tournament parameter for the
     * same reason.
     */
    public function store(Request $request, Tournament $tournament, Matchs $match): RedirectResponse
    {
        $allowedOrganizationIds = $this->allowedOrganizationIds($request);

        abort_if($allowedOrganizationIds !== null && $allowedOrganizationIds->isEmpty(), 403);

        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2048'],
            'language_code' => ['required', 'string', 'max:5', Rule::in(array_keys(app(Countries::class)->list()))],
            'game_map_id' => ['nullable', 'integer', Rule::exists('game_maps', 'id')->where('match_id', $match->id)],
            'organization_id' => ['nullable', 'integer', 'exists:organization,id'],
        ]);

        if ($allowedOrganizationIds !== null) {
            $organizationId = $validated['organization_id'] ?? null;

            if (! $organizationId) {
                abort_unless($allowedOrganizationIds->count() === 1, 422);
                $validated['organization_id'] = $allowedOrganizationIds->first();
            } else {
                abort_unless($allowedOrganizationIds->contains($organizationId), 403);
            }
        }

        $vod = $match->vods()->create([
            'game_map_id' => $validated['game_map_id'] ?? null,
            'organization_id' => $validated['organization_id'] ?? null,
            'url' => $validated['url'],
            'language_code' => $validated['language_code'],
        ]);

        $match->touch();

        activity('tournament')->performedOn($match)->causedBy($request->user())
            ->withProperties(ActivityChangeSet::fromCreated($vod, ['game_map_id', 'organization_id', 'url', 'language_code'])->mergeInto(['vod_id' => $vod->id]))
            ->log('match.vod_linked');

        return back()->with('status', 'vod-linked');
    }

    /** @see store() docblock — $tournament is unused but required for correct implicit binding of $match/$vod. */
    public function update(Request $request, Tournament $tournament, Matchs $match, Vod $vod): RedirectResponse
    {
        abort_unless($vod->match_id === $match->id, 404);

        $this->ensureCanManage($request, $vod);

        $allowedOrganizationIds = $this->allowedOrganizationIds($request);

        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2048'],
            'language_code' => ['required', 'string', 'max:5', Rule::in(array_keys(app(Countries::class)->list()))],
            'game_map_id' => ['nullable', 'integer', Rule::exists('game_maps', 'id')->where('match_id', $match->id)],
            'organization_id' => ['nullable', 'integer', 'exists:organization,id'],
        ]);

        if ($allowedOrganizationIds !== null) {
            $organizationId = $validated['organization_id'] ?? null;

            if (! $organizationId) {
                abort_unless($allowedOrganizationIds->count() === 1, 422);
                $validated['organization_id'] = $allowedOrganizationIds->first();
            } else {
                abort_unless($allowedOrganizationIds->contains($organizationId), 403);
            }
        }

        $vod->update([
            'game_map_id' => $validated['game_map_id'] ?? null,
            'organization_id' => $validated['organization_id'] ?? null,
            'url' => $validated['url'],
            'language_code' => $validated['language_code'],
        ]);

        $match->touch();

        activity('tournament')->performedOn($match)->causedBy($request->user())
            ->withProperties(ActivityChangeSet::fromModel($vod, ['game_map_id', 'organization_id', 'url', 'language_code'])->mergeInto(['vod_id' => $vod->id]))
            ->log('match.vod_updated');

        return back()->with('status', 'vod-updated');
    }

    /** @see store() docblock — $tournament is unused but required for correct implicit binding of $match/$vod. */
    public function destroy(Request $request, Tournament $tournament, Matchs $match, Vod $vod): RedirectResponse
    {
        abort_unless($vod->match_id === $match->id, 404);

        $this->ensureCanManage($request, $vod);

        $vodId = $vod->id;
        $vod->delete();
        $match->touch();

        activity('tournament')->performedOn($match)->causedBy($request->user())
            ->withProperties(['vod_id' => $vodId])
            ->log('match.vod_unlinked');

        return back()->with('status', 'vod-unlinked');
    }

    /**
     * @return Collection<int, int>|null null means unrestricted (site admin)
     */
    private function allowedOrganizationIds(Request $request, ?Organization $organization = null): ?Collection
    {
        $user = $request->user();

        if ($organization) {
            $allowed = app(OrganizationAccessService::class)
                ->hasOrganizationPermission($user, $organization, self::LINK_PERMISSION, 'organization.vods.link');

            return $allowed ? collect([$organization->id]) : collect();
        }

        return $user->can(self::LINK_PERMISSION) ? null : OrganizationScope::organizationIdsWithPermission($user->id, 'organization.vods.link');
    }

    private function ensureCanManage(Request $request, Vod $vod): void
    {
        $user = $request->user();

        if ($user->can(self::LINK_PERMISSION)) {
            return;
        }

        $allowed = $vod->organization_id
            && OrganizationScope::organizationIdsWithPermission($user->id, 'organization.vods.link')->contains($vod->organization_id);

        abort_unless($allowed, 403);
    }
}
