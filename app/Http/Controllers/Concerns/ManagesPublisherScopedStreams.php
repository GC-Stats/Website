<?php

/**
 * GC-Stats — Publisher-scoped stream channel access
 *
 * Shared by Admin\StreamChannelController and Admin\MatchStreamController: a
 * stream channel is manageable by a site editor holding the matching
 * AdminPermission, or by a publisher's own member holding the matching
 * 'publisher.streams.*' permission (see App\Support\PublisherPermissions) on
 * the channel's publisher. Mirrors ManagesPublisherScopedNews.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Concerns;

use App\Models\StreamChannel;
use App\Support\PublisherScope;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait ManagesPublisherScopedStreams
{
    /**
     * @return Collection<int, int>
     */
    private function allowedStreamPublisherIds(Request $request, string $publisherPermission): Collection
    {
        return PublisherScope::publisherIdsWithPermission($request->user()->id, $publisherPermission);
    }

    private function ensureCanManageChannel(Request $request, StreamChannel $channel, string $adminPermission, string $publisherPermission): void
    {
        abort_unless($this->canManageChannel($request, $channel, $adminPermission, $publisherPermission), 403);
    }

    private function canManageChannel(Request $request, StreamChannel $channel, string $adminPermission, string $publisherPermission): bool
    {
        $user = $request->user();

        if ($user->can($adminPermission)) {
            return true;
        }

        return $channel->publisher_id
            && $this->allowedStreamPublisherIds($request, $publisherPermission)->contains($channel->publisher_id);
    }
}
