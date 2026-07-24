<?php

/**
 * GC-Stats — Admin: stream channels
 *
 * CRUD for streaming channels (YouTube/Twitch/TikTok) that can be linked to
 * matches (see Admin\MatchStreamController). Reachable by site editors
 * (streams.channels.*) or by a publisher's own member with the matching
 * 'publisher.streams.*' permission on the channel's publisher (guard
 * 'publisher', see App\Support\PublisherPermissions) — see
 * App\Http\Controllers\Concerns\ManagesPublisherScopedStreams. A channel
 * with no publisher_id is an admin-only channel, same pattern as
 * News::publisher_id.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ManagesPublisherScopedStreams;
use App\Http\Controllers\Controller;
use App\Models\NewsPublisher;
use App\Models\StreamChannel;
use App\Support\Countries;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class StreamChannelController extends Controller
{
    use ManagesPublisherScopedStreams;

    private const SORTABLE = ['name', 'platform', 'language_code', 'publisher'];

    public function index(Request $request): View
    {
        $user = $request->user();
        $allowedPublisherIds = $user->can('streams.channels.view') ? null : $this->allowedStreamPublisherIds($request, 'publisher.streams.view');

        abort_if($allowedPublisherIds !== null && $allowedPublisherIds->isEmpty(), 403);

        $search = $request->get('q');
        $platform = $request->get('platform');
        $publisherId = $request->get('publisher_id');

        [$sort, $direction] = $this->resolveSort($request, self::SORTABLE, 'name', 'asc');

        $channels = StreamChannel::query()
            ->with('publisher')
            ->when($allowedPublisherIds !== null, fn ($query) => $query->whereIn('publisher_id', $allowedPublisherIds))
            ->when($search, fn ($query) => $query->where('name', 'like', '%'.$this->escapeLike($search).'%'))
            ->when($platform, fn ($query) => $query->where('platform', $platform))
            ->when($publisherId, fn ($query) => $query->where('publisher_id', $publisherId))
            ->when($sort === 'name', fn ($query) => $query->orderBy('name', $direction))
            ->when($sort === 'platform', fn ($query) => $query->orderBy('platform', $direction))
            ->when($sort === 'language_code', fn ($query) => $query->orderBy('language_code', $direction))
            ->when($sort === 'publisher', fn ($query) => $query
                ->select('stream_channels.*')
                ->leftJoin('news_publishers', 'news_publishers.id', '=', 'stream_channels.publisher_id')
                ->orderBy('news_publishers.name', $direction))
            ->orderByDesc('stream_channels.id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.streams.index', [
            'channels' => $channels,
            'search' => $search ?? '',
            'platform' => $platform ?? '',
            'sort' => $sort,
            'direction' => $direction,
            'platforms' => StreamChannel::PLATFORMS,
            'publishers' => NewsPublisher::orderBy('name')->get(['id', 'name']),
            'editablePublisherIds' => $user->can('streams.channels.edit') ? null : $this->allowedStreamPublisherIds($request, 'publisher.streams.edit'),
            'deletablePublisherIds' => $user->can('streams.channels.delete') ? null : $this->allowedStreamPublisherIds($request, 'publisher.streams.delete'),
        ]);
    }

    public function create(Request $request): View
    {
        $allowedPublisherIds = $request->user()->can('streams.channels.create') ? null : $this->allowedStreamPublisherIds($request, 'publisher.streams.edit');

        abort_if($allowedPublisherIds !== null && $allowedPublisherIds->isEmpty(), 403);

        return view('admin.streams.create', $this->formData(null, $allowedPublisherIds));
    }

    public function store(Request $request): RedirectResponse
    {
        $allowedPublisherIds = $request->user()->can('streams.channels.create') ? null : $this->allowedStreamPublisherIds($request, 'publisher.streams.edit');

        abort_if($allowedPublisherIds !== null && $allowedPublisherIds->isEmpty(), 403);

        $validated = $this->validated($request, null, $allowedPublisherIds);

        $channel = StreamChannel::create($validated);

        return redirect()->route('admin.streams.edit', $channel)->with('status', 'channel-created');
    }

    public function edit(Request $request, StreamChannel $channel): View
    {
        $this->ensureCanManageChannel($request, $channel, 'streams.channels.edit', 'publisher.streams.edit');

        $restricted = ! $request->user()->can('streams.channels.edit');

        return view('admin.streams.edit', $this->formData($channel, $restricted ? $this->allowedStreamPublisherIds($request, 'publisher.streams.edit') : null));
    }

    public function update(Request $request, StreamChannel $channel): RedirectResponse
    {
        $this->ensureCanManageChannel($request, $channel, 'streams.channels.edit', 'publisher.streams.edit');

        $restricted = ! $request->user()->can('streams.channels.edit');
        $allowedPublisherIds = $restricted ? $this->allowedStreamPublisherIds($request, 'publisher.streams.edit') : null;

        $validated = $this->validated($request, $channel, $allowedPublisherIds);

        $channel->update($validated);

        return back()->with('status', 'channel-updated');
    }

    public function destroy(Request $request, StreamChannel $channel): RedirectResponse
    {
        $this->ensureCanManageChannel($request, $channel, 'streams.channels.delete', 'publisher.streams.delete');

        $channel->delete();

        return redirect()->route('admin.streams.index')->with('status', 'channel-deleted');
    }

    /**
     * @param  Collection<int, int>|null  $allowedPublisherIds  when not null, 'publisher_id' must be one of these
     */
    private function validated(Request $request, ?StreamChannel $channel, ?Collection $allowedPublisherIds): array
    {
        $validated = $request->validate([
            'publisher_id' => ['nullable', 'integer', 'exists:news_publishers,id'],
            'name' => ['required', 'string', 'max:255'],
            'platform' => ['required', 'string', Rule::in(StreamChannel::PLATFORMS)],
            'url' => ['required', 'url', 'max:2048'],
            'language_code' => ['required', 'string', 'max:5', Rule::in(array_keys(app(Countries::class)->list()))],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $publisherId = $validated['publisher_id'] ?? null;
        $publisherUnchanged = $channel && (int) $publisherId === (int) $channel->publisher_id;

        if ($allowedPublisherIds !== null) {
            if (! $publisherId) {
                // A publisher-scoped editor can't create/leave a channel
                // admin-only (publisher_id null) — it must belong to one of
                // their own publishers.
                abort_unless($allowedPublisherIds->count() === 1, 422);
                $validated['publisher_id'] = $allowedPublisherIds->first();
            } elseif (! $publisherUnchanged) {
                abort_unless($allowedPublisherIds->contains($publisherId), 403);
            }
        }

        return $validated;
    }

    /**
     * @param  Collection<int, int>|null  $restrictToPublisherIds  when not null, the publisher picker only lists these
     */
    private function formData(?StreamChannel $channel, ?Collection $restrictToPublisherIds): array
    {
        return [
            'channel' => $channel,
            'restricted' => $restrictToPublisherIds !== null,
            'platforms' => StreamChannel::PLATFORMS,
            'countries' => app(Countries::class)->list(),
            'publishers' => NewsPublisher::query()
                ->when($restrictToPublisherIds !== null, fn ($query) => $query->whereIn('id', $restrictToPublisherIds))
                ->orderBy('name')->get(['id', 'name']),
        ];
    }
}
