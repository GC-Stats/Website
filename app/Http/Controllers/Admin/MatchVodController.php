<?php

/**
 * GC-Stats — Admin: match VODs
 *
 * Creates/removes a Vod row directly on a match (and, optionally, one of
 * its maps) — see App\Models\Vod's docblock for why this has no separate
 * "channel" CRUD unlike streams: a VOD is a one-off link, not a reusable
 * entity.
 *
 * Gated by its own permission pair — vods.matches.link / publisher.vods.link
 * — mirroring Admin\MatchStreamController, including the same "not gated by
 * matches.view" reasoning: a publisher's own member has no access to the
 * admin match list/show pages at all, so the "add a VOD" form lives on the
 * public match page too (see resources/views/match.blade.php) and posts
 * here directly.
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
use App\Models\NewsPublisher;
use App\Models\Tournament;
use App\Models\Vod;
use App\Support\Countries;
use App\Support\PublisherScope;
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

    /**
     * "Liste tout" — every match that currently has at least one VOD,
     * grouped by match. Restricted to VODs the current user can see when
     * they're a publisher-scoped editor rather than a full site editor.
     */
    public function index(Request $request): View
    {
        $allowedPublisherIds = $this->allowedPublisherIds($request);

        abort_if($allowedPublisherIds !== null && $allowedPublisherIds->isEmpty(), 403);

        [$sort, $direction] = $this->resolveSort($request, self::SORTABLE, 'scheduled_at', 'desc');

        $matches = Matchs::query()
            ->select('matches.*')
            ->whereHas('vods', fn ($query) => $this->scopeToAllowedPublishers($query, $allowedPublisherIds))
            ->with([
                'teamA:id,name,short_name', 'teamB:id,name,short_name', 'tournament:id,name',
                'tournamentPhase.parent.parent.parent.parent',
                'vods' => fn ($query) => $this->scopeToAllowedPublishers($query, $allowedPublisherIds)->with(['publisher', 'gameMap']),
                'game_maps' => fn ($query) => $query->orderBy('order'),
            ])
            ->when($sort === 'tournament', fn ($query) => $query
                ->leftJoin('tournaments', 'tournaments.id', '=', 'matches.tournament_id')
                ->orderBy('tournaments.name', $direction))
            ->when($sort === 'scheduled_at', fn ($query) => $query->orderBy('matches.scheduled_at', $direction))
            ->paginate(25)
            ->withQueryString();

        return view('admin.vods.matches.index', [
            'matches' => $matches,
            'sort' => $sort,
            'direction' => $direction,
            'countries' => app(Countries::class)->list(),
            'vodsRestricted' => $allowedPublisherIds !== null,
            'vodPublishers' => NewsPublisher::query()
                ->when($allowedPublisherIds !== null, fn ($query) => $query->whereIn('id', $allowedPublisherIds))
                ->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request): View
    {
        $allowedPublisherIds = $this->allowedPublisherIds($request);

        abort_if($allowedPublisherIds !== null && $allowedPublisherIds->isEmpty(), 403);

        return view('admin.vods.matches.create', ['countries' => app(Countries::class)->list()]);
    }

    /**
     * @param  Collection<int, int>|null  $allowedPublisherIds
     */
    private function scopeToAllowedPublishers($query, ?Collection $allowedPublisherIds)
    {
        return $allowedPublisherIds === null ? $query : $query->whereIn('publisher_id', $allowedPublisherIds);
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
        $allowedPublisherIds = $this->allowedPublisherIds($request);

        abort_if($allowedPublisherIds !== null && $allowedPublisherIds->isEmpty(), 403);

        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2048'],
            'language_code' => ['required', 'string', 'max:5', Rule::in(array_keys(app(Countries::class)->list()))],
            'game_map_id' => ['nullable', 'integer', Rule::exists('game_maps', 'id')->where('match_id', $match->id)],
            'publisher_id' => ['nullable', 'integer', 'exists:news_publishers,id'],
        ]);

        if ($allowedPublisherIds !== null) {
            $publisherId = $validated['publisher_id'] ?? null;

            if (! $publisherId) {
                abort_unless($allowedPublisherIds->count() === 1, 422);
                $validated['publisher_id'] = $allowedPublisherIds->first();
            } else {
                abort_unless($allowedPublisherIds->contains($publisherId), 403);
            }
        }

        $match->vods()->create([
            'game_map_id' => $validated['game_map_id'] ?? null,
            'publisher_id' => $validated['publisher_id'] ?? null,
            'url' => $validated['url'],
            'language_code' => $validated['language_code'],
        ]);

        $match->touch();

        return back()->with('status', 'vod-linked');
    }

    /** @see store() docblock — $tournament is unused but required for correct implicit binding of $match/$vod. */
    public function update(Request $request, Tournament $tournament, Matchs $match, Vod $vod): RedirectResponse
    {
        abort_unless($vod->match_id === $match->id, 404);

        $this->ensureCanManage($request, $vod);

        $allowedPublisherIds = $this->allowedPublisherIds($request);

        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2048'],
            'language_code' => ['required', 'string', 'max:5', Rule::in(array_keys(app(Countries::class)->list()))],
            'game_map_id' => ['nullable', 'integer', Rule::exists('game_maps', 'id')->where('match_id', $match->id)],
            'publisher_id' => ['nullable', 'integer', 'exists:news_publishers,id'],
        ]);

        if ($allowedPublisherIds !== null) {
            $publisherId = $validated['publisher_id'] ?? null;

            if (! $publisherId) {
                abort_unless($allowedPublisherIds->count() === 1, 422);
                $validated['publisher_id'] = $allowedPublisherIds->first();
            } else {
                abort_unless($allowedPublisherIds->contains($publisherId), 403);
            }
        }

        $vod->update([
            'game_map_id' => $validated['game_map_id'] ?? null,
            'publisher_id' => $validated['publisher_id'] ?? null,
            'url' => $validated['url'],
            'language_code' => $validated['language_code'],
        ]);

        $match->touch();

        return back()->with('status', 'vod-updated');
    }

    /** @see store() docblock — $tournament is unused but required for correct implicit binding of $match/$vod. */
    public function destroy(Request $request, Tournament $tournament, Matchs $match, Vod $vod): RedirectResponse
    {
        abort_unless($vod->match_id === $match->id, 404);

        $this->ensureCanManage($request, $vod);

        $vod->delete();
        $match->touch();

        return back()->with('status', 'vod-unlinked');
    }

    /**
     * @return Collection<int, int>|null null means unrestricted (site admin)
     */
    private function allowedPublisherIds(Request $request): ?Collection
    {
        $user = $request->user();

        return $user->can(self::LINK_PERMISSION) ? null : PublisherScope::publisherIdsWithPermission($user->id, 'publisher.vods.link');
    }

    private function ensureCanManage(Request $request, Vod $vod): void
    {
        $user = $request->user();

        if ($user->can(self::LINK_PERMISSION)) {
            return;
        }

        $allowed = $vod->publisher_id
            && PublisherScope::publisherIdsWithPermission($user->id, 'publisher.vods.link')->contains($vod->publisher_id);

        abort_unless($allowed, 403);
    }
}
