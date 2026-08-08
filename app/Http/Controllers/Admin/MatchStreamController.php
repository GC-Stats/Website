<?php

/**
 * GC-Stats — Admin: match/stream links
 *
 * Attaches/detaches an existing StreamChannel to a match (see
 * Matchs::streams()), plus the search endpoint backing the channel picker
 * (see resources/views/components/relation-picker.blade.php, reused here
 * the same way Admin\NewsController::searchRelations feeds the
 * players/teams/tournaments picker on an article).
 *
 * Deliberately separate from channel CRUD (Admin\StreamChannelController)
 * and gated by its own permission pair — streams.matches.link /
 * organization.streams.link — so an organization's editor can be granted the
 * right to link their own channels to matches without also being able to
 * create/edit/delete channels, and vice versa.
 *
 * These routes live under the admin.matches.* prefix but are NOT gated by
 * matches.view: an organization's own member has no access to the admin
 * match list/show pages at all, so the "link a stream" button lives on the
 * public match page instead (see resources/views/match.blade.php) and posts
 * here directly — authorization is fully self-contained in ensureCanLink()/
 * search(), independent of which page the request came from.
 *
 * index()/create() are additionally dual-context like Admin\NewsController
 * — reachable at both admin.streams.matches.* (flat, cross-organization)
 * and organization-dashboard.streams.matches.* (scoped to the
 * {organization} bound in the dashboard URL). store()/update()/destroy()/
 * linkMany()/search() don't need the same treatment: they're id-based
 * mutations/lookups whose forms already point at fixed routes regardless of
 * which page posted to them, and their authorization (ensureCanLink()) is
 * already independent of the route.
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
use App\Models\StreamChannel;
use App\Models\Tournament;
use App\Services\OrganizationAccessService;
use App\Support\OrganizationScope;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class MatchStreamController extends Controller
{
    use SearchesMatchesForLinking;

    /** @see SearchesMatchesForLinking */
    private const LINK_PERMISSION = 'streams.matches.link';

    private const SORTABLE = ['scheduled_at', 'tournament'];

    private function isDashboard(): bool
    {
        return request()->routeIs('organization-dashboard.*');
    }

    private function routePrefix(): string
    {
        return $this->isDashboard() ? 'organization-dashboard.streams.matches.' : 'admin.streams.matches.';
    }

    private function viewName(string $page): string
    {
        return $this->isDashboard() ? "organization.dashboard.streams.matches.{$page}" : "admin.streams.matches.{$page}";
    }

    /**
     * "Liste tout" — every match that currently has at least one stream
     * linked, grouped by match (a match may carry several channels/
     * languages). Restricted to channels the current user can see when
     * they're an organization-scoped editor rather than a full site editor
     * (or, in dashboard mode, to that one organization specifically).
     *
     * Finished matches are hidden by default (?status=all / =finished
     * overrides this) — once a match is over a VOD replaces the stream
     * link in practice, so keeping finished matches out by default keeps
     * this list focused on what's actually still worth managing.
     */
    public function index(Request $request, ?Organization $organization = null): View
    {
        $allowedOrganizationIds = $this->allowedOrganizationIds($request, $organization);

        abort_if($allowedOrganizationIds !== null && $allowedOrganizationIds->isEmpty(), 403);

        $status = $request->get('status', 'active');
        [$sort, $direction] = $this->resolveSort($request, self::SORTABLE, 'scheduled_at', 'desc');

        $matches = Matchs::query()
            ->select('matches.*')
            ->whereHas('streams', fn ($query) => $this->scopeToAllowedOrganizations($query, $allowedOrganizationIds))
            ->when($status === 'active', fn ($query) => $query->where('matches.status', '!=', 'finished'))
            ->when(in_array($status, ['upcoming', 'live', 'finished'], true), fn ($query) => $query->where('matches.status', $status))
            ->with([
                'teamA:id,name,short_name', 'teamB:id,name,short_name', 'tournament:id,name',
                'tournamentPhase.parent.parent.parent.parent',
                'streams' => fn ($query) => $this->scopeToAllowedOrganizations($query, $allowedOrganizationIds)->with('organization'),
            ])
            ->when($sort === 'tournament', fn ($query) => $query
                ->leftJoin('tournaments', 'tournaments.id', '=', 'matches.tournament_id')
                ->orderBy('tournaments.name', $direction))
            ->when($sort === 'scheduled_at', fn ($query) => $query->orderBy('matches.scheduled_at', $direction))
            ->paginate(25)
            ->withQueryString();

        return view($this->viewName('index'), [
            'matches' => $matches, 'status' => $status, 'sort' => $sort, 'direction' => $direction, 'organization' => $organization,
        ]);
    }

    public function create(Request $request, ?Organization $organization = null): View
    {
        $allowedOrganizationIds = $this->allowedOrganizationIds($request, $organization);

        abort_if($allowedOrganizationIds !== null && $allowedOrganizationIds->isEmpty(), 403);

        return view($this->viewName('create'), ['organization' => $organization, 'routePrefix' => $this->routePrefix()]);
    }

    /**
     * @return Collection<int, int>|null null means unrestricted (site admin)
     */
    private function allowedOrganizationIds(Request $request, ?Organization $organization = null): ?Collection
    {
        $user = $request->user();

        if ($organization) {
            $allowed = app(OrganizationAccessService::class)
                ->hasOrganizationPermission($user, $organization, self::LINK_PERMISSION, 'organization.streams.link');

            return $allowed ? collect([$organization->id]) : collect();
        }

        return $user->can(self::LINK_PERMISSION) ? null : OrganizationScope::organizationIdsWithPermission($user->id, 'organization.streams.link');
    }

    /**
     * @param  Collection<int, int>|null  $allowedOrganizationIds
     */
    private function scopeToAllowedOrganizations($query, ?Collection $allowedOrganizationIds)
    {
        return $allowedOrganizationIds === null ? $query : $query->whereIn('organization_id', $allowedOrganizationIds);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:100'],
            'match_id' => ['nullable', 'integer', 'exists:matches,id'],
        ]);

        $allowedOrganizationIds = $this->allowedOrganizationIds($request);

        abort_if($allowedOrganizationIds !== null && $allowedOrganizationIds->isEmpty(), 403);

        $alreadyLinkedIds = isset($validated['match_id'])
            ? Matchs::findOrFail($validated['match_id'])->streams()->pluck('stream_channels.id')
            : collect();

        $channels = StreamChannel::query()
            ->active()
            ->where('name', 'like', '%'.$this->escapeLike($validated['q']).'%')
            ->when($allowedOrganizationIds !== null, fn ($query) => $query->whereIn('organization_id', $allowedOrganizationIds))
            ->whereNotIn('id', $alreadyLinkedIds)
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'platform']);

        return response()->json($channels->map(fn ($channel) => [
            'id' => $channel->id,
            'label' => $channel->name.' ('.ucfirst($channel->platform).')',
        ]));
    }

    /**
     * $tournament is unused but required: Laravel's implicit route-model
     * binding splices resolved parameters positionally, and the route also
     * carries a {tournament} segment ahead of {match} — dropping it from
     * the signature misaligns the splice and $match ends up receiving the
     * raw {tournament} string instead of the bound Matchs model. Every
     * other method in the sibling Admin\MatchController keeps its
     * $tournament parameter for the same reason.
     */
    public function store(Request $request, Tournament $tournament, Matchs $match): RedirectResponse
    {
        $validated = $request->validate([
            'stream_channel_id' => ['required', 'array', 'min:1'],
            'stream_channel_id.*' => ['integer', 'exists:stream_channels,id'],
        ]);

        $channels = StreamChannel::whereIn('id', $validated['stream_channel_id'])->get();

        foreach ($channels as $channel) {
            $this->ensureCanLink($request, $channel);
        }

        $match->streams()->syncWithoutDetaching($channels->pluck('id'));

        // The pivot table isn't covered by MatchObserver — touch() re-saves
        // the match so its 'saved' event fires and busts the public match
        // page cache/CDN edge cache (see App\Observers\MatchObserver and
        // App\Console\Commands\CacheCommands\ClearMatchesCache for the same
        // touch()-to-bust-cache idiom).
        $match->touch();

        activity('tournament')->performedOn($match)->causedBy($request->user())
            ->withProperties(['stream_channel_id' => $channels->pluck('id')->all()])
            ->log('match.streams_linked');

        return back()->with('status', 'stream-linked');
    }

    /**
     * Bulk variant used by the create() wizard: links every selected
     * channel to every selected match in one go (a cross-product), unlike
     * store() which targets a single match reached via its own route.
     * Matches/channels are both identified by id in the body rather than
     * via route binding, since there's no single {match} in the URL here.
     */
    public function linkMany(Request $request, ?Organization $organization = null): RedirectResponse
    {
        $validated = $request->validate([
            'match_id' => ['required', 'array', 'min:1'],
            'match_id.*' => ['integer', 'exists:matches,id'],
            'stream_channel_id' => ['required', 'array', 'min:1'],
            'stream_channel_id.*' => ['integer', 'exists:stream_channels,id'],
        ]);

        $channels = StreamChannel::whereIn('id', $validated['stream_channel_id'])->get();

        foreach ($channels as $channel) {
            $this->ensureCanLink($request, $channel);
        }

        $channelIds = $channels->pluck('id');
        $matches = Matchs::whereIn('id', $validated['match_id'])->get();

        foreach ($matches as $match) {
            $match->streams()->syncWithoutDetaching($channelIds);
            $match->touch();

            activity('tournament')->performedOn($match)->causedBy($request->user())
                ->withProperties(['stream_channel_id' => $channelIds->all()])
                ->log('match.streams_linked');
        }

        return redirect()->route($this->routePrefix().'index', $organization ?? [])->with('status', 'stream-linked');
    }

    /**
     * Global edit for a match's stream links: the picker is preselected
     * with every channel currently linked, and submitting fully replaces
     * that set (sync, not syncWithoutDetaching) — unlike store(), which
     * only ever adds. Individual unlinking still goes through destroy().
     *
     * @see store() docblock — $tournament is unused but required for correct implicit binding of $match.
     */
    public function update(Request $request, Tournament $tournament, Matchs $match): RedirectResponse
    {
        $validated = $request->validate([
            'stream_channel_id' => ['required', 'array', 'min:1'],
            'stream_channel_id.*' => ['integer', 'exists:stream_channels,id'],
        ]);

        $channels = StreamChannel::whereIn('id', $validated['stream_channel_id'])->get();

        foreach ($match->streams->merge($channels)->unique('id') as $affectedChannel) {
            $this->ensureCanLink($request, $affectedChannel);
        }

        $before = $match->streams->pluck('id')->all();

        $match->streams()->sync($channels->pluck('id'));
        $match->touch();

        activity('tournament')->performedOn($match)->causedBy($request->user())
            ->withProperties(['changes' => ['stream_channel_id' => ['old' => $before, 'new' => $channels->pluck('id')->all()]]])
            ->log('match.streams_updated');

        return back()->with('status', 'stream-updated');
    }

    /** @see store() docblock — $tournament is unused but required for correct implicit binding of $match/$channel. */
    public function destroy(Request $request, Tournament $tournament, Matchs $match, StreamChannel $channel): RedirectResponse
    {
        $this->ensureCanLink($request, $channel);

        $match->streams()->detach($channel->id);
        $match->touch();

        activity('tournament')->performedOn($match)->causedBy($request->user())
            ->withProperties(['stream_channel_id' => $channel->id])
            ->log('match.stream_unlinked');

        return back()->with('status', 'stream-unlinked');
    }

    private function ensureCanLink(Request $request, StreamChannel $channel): void
    {
        $user = $request->user();

        if ($user->can(self::LINK_PERMISSION)) {
            return;
        }

        $allowed = $channel->organization_id
            && OrganizationScope::organizationIdsWithPermission($user->id, 'organization.streams.link')->contains($channel->organization_id);

        abort_unless($allowed, 403);
    }
}
