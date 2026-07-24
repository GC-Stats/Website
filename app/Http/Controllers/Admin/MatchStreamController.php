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
 * publisher.streams.link — so a publisher's editor can be granted the right
 * to link their own channels to matches without also being able to
 * create/edit/delete channels, and vice versa.
 *
 * These routes live under the admin.matches.* prefix but are NOT gated by
 * matches.view: a publisher's own member has no access to the admin match
 * list/show pages at all, so the "link a stream" button lives on the public
 * match page instead (see resources/views/match.blade.php) and posts here
 * directly — authorization is fully self-contained in ensureCanLink()/
 * search(), independent of which page the request came from.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\SearchesMatchesForLinking;
use App\Http\Controllers\Controller;
use App\Models\Matchs;
use App\Models\StreamChannel;
use App\Models\Tournament;
use App\Support\PublisherScope;
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

    /**
     * "Liste tout" — every match that currently has at least one stream
     * linked, grouped by match (a match may carry several channels/
     * languages). Restricted to channels the current user can see when
     * they're a publisher-scoped editor rather than a full site editor.
     *
     * Finished matches are hidden by default (?status=all / =finished
     * overrides this) — once a match is over a VOD replaces the stream
     * link in practice, so keeping finished matches out by default keeps
     * this list focused on what's actually still worth managing.
     */
    public function index(Request $request): View
    {
        $allowedPublisherIds = $this->allowedPublisherIds($request);

        abort_if($allowedPublisherIds !== null && $allowedPublisherIds->isEmpty(), 403);

        $status = $request->get('status', 'active');
        [$sort, $direction] = $this->resolveSort($request, self::SORTABLE, 'scheduled_at', 'desc');

        $matches = Matchs::query()
            ->select('matches.*')
            ->whereHas('streams', fn ($query) => $this->scopeToAllowedPublishers($query, $allowedPublisherIds))
            ->when($status === 'active', fn ($query) => $query->where('matches.status', '!=', 'finished'))
            ->when(in_array($status, ['upcoming', 'live', 'finished'], true), fn ($query) => $query->where('matches.status', $status))
            ->with([
                'teamA:id,name,short_name', 'teamB:id,name,short_name', 'tournament:id,name',
                'tournamentPhase.parent.parent.parent.parent',
                'streams' => fn ($query) => $this->scopeToAllowedPublishers($query, $allowedPublisherIds)->with('publisher'),
            ])
            ->when($sort === 'tournament', fn ($query) => $query
                ->leftJoin('tournaments', 'tournaments.id', '=', 'matches.tournament_id')
                ->orderBy('tournaments.name', $direction))
            ->when($sort === 'scheduled_at', fn ($query) => $query->orderBy('matches.scheduled_at', $direction))
            ->paginate(25)
            ->withQueryString();

        return view('admin.streams.matches.index', ['matches' => $matches, 'status' => $status, 'sort' => $sort, 'direction' => $direction]);
    }

    public function create(Request $request): View
    {
        $allowedPublisherIds = $this->allowedPublisherIds($request);

        abort_if($allowedPublisherIds !== null && $allowedPublisherIds->isEmpty(), 403);

        return view('admin.streams.matches.create');
    }

    /**
     * @return Collection<int, int>|null null means unrestricted (site admin)
     */
    private function allowedPublisherIds(Request $request): ?Collection
    {
        $user = $request->user();

        return $user->can(self::LINK_PERMISSION) ? null : PublisherScope::publisherIdsWithPermission($user->id, 'publisher.streams.link');
    }

    /**
     * @param  Collection<int, int>|null  $allowedPublisherIds
     */
    private function scopeToAllowedPublishers($query, ?Collection $allowedPublisherIds)
    {
        return $allowedPublisherIds === null ? $query : $query->whereIn('publisher_id', $allowedPublisherIds);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:100'],
            'match_id' => ['nullable', 'integer', 'exists:matches,id'],
        ]);

        $allowedPublisherIds = $this->allowedPublisherIds($request);

        abort_if($allowedPublisherIds !== null && $allowedPublisherIds->isEmpty(), 403);

        $alreadyLinkedIds = isset($validated['match_id'])
            ? Matchs::findOrFail($validated['match_id'])->streams()->pluck('stream_channels.id')
            : collect();

        $channels = StreamChannel::query()
            ->active()
            ->where('name', 'like', '%'.$this->escapeLike($validated['q']).'%')
            ->when($allowedPublisherIds !== null, fn ($query) => $query->whereIn('publisher_id', $allowedPublisherIds))
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

        return back()->with('status', 'stream-linked');
    }

    /**
     * Bulk variant used by the create() wizard: links every selected
     * channel to every selected match in one go (a cross-product), unlike
     * store() which targets a single match reached via its own route.
     * Matches/channels are both identified by id in the body rather than
     * via route binding, since there's no single {match} in the URL here.
     */
    public function linkMany(Request $request): RedirectResponse
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
        }

        return redirect()->route('admin.streams.matches.index')->with('status', 'stream-linked');
    }

    /** @see store() docblock — $tournament is unused but required for correct implicit binding of $match/$channel. */
    public function destroy(Request $request, Tournament $tournament, Matchs $match, StreamChannel $channel): RedirectResponse
    {
        $this->ensureCanLink($request, $channel);

        $match->streams()->detach($channel->id);
        $match->touch();

        return back()->with('status', 'stream-unlinked');
    }

    private function ensureCanLink(Request $request, StreamChannel $channel): void
    {
        $user = $request->user();

        if ($user->can(self::LINK_PERMISSION)) {
            return;
        }

        $allowed = $channel->publisher_id
            && PublisherScope::publisherIdsWithPermission($user->id, 'publisher.streams.link')->contains($channel->publisher_id);

        abort_unless($allowed, 403);
    }
}
