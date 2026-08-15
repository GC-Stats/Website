<?php

/**
 * GC-Stats — Has reactions trait
 *
 * Shared logic for models that can receive emote reactions (News for now
 * — see resources/views/livewire/reaction-bar.blade.php). Reactions
 * themselves are toggled via App\Services\ReactionService, never created
 * or updated directly.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Models\Concerns;

use App\Models\Emote;
use App\Models\Reaction;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

trait HasReactions
{
    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactable');
    }

    /**
     * Cache key for this reactable's aggregate emote counts — deliberately
     * excludes the acting user, so one cached entry serves every viewer
     * (the per-user "reacted" flag is resolved separately, uncached, in
     * reactionSummary() below). See App\Services\ReactionService, the only
     * writer of reactions, for where this gets busted.
     */
    public function reactionCountsCacheKey(): string
    {
        return "reactions:counts:{$this->getMorphClass()}:{$this->getKey()}";
    }

    public function forgetReactionCountsCache(): void
    {
        Cache::forget($this->reactionCountsCacheKey());
    }

    /**
     * Reactions grouped by emote, most-reacted first. Aggregates counts in
     * SQL rather than loading every reaction row — this scales with the
     * number of distinct emotes used, not the (potentially much larger)
     * total reaction count. Every forum message renders its own #[Lazy]
     * reaction-bar instance, each running this on its own initial load —
     * caching the count aggregate (shared across every viewer of that
     * message/news item) turns most of those into cache hits instead of a
     * fresh groupBy query each time.
     *
     * @return Collection<int, array{emote: Emote, count: int, reacted: bool}>
     */
    public function reactionSummary(?int $currentUserId = null): Collection
    {
        $countsQuery = fn () => $this->reactions()
            ->selectRaw('emote_id, count(*) as count')
            ->groupBy('emote_id')
            ->orderByDesc('count')
            ->pluck('count', 'emote_id');

        $counts = Cache::remember($this->reactionCountsCacheKey(), now()->addSeconds(30), $countsQuery);

        // Same guard as App\Models\Emote's cached accessors: a cache entry
        // written before a deploy can unserialize as __PHP_Incomplete_Class
        // if the underlying class definition shifted underneath it since.
        if (! $counts instanceof Collection) {
            $this->forgetReactionCountsCache();
            $counts = $countsQuery();
        }

        if ($counts->isEmpty()) {
            return collect();
        }

        $emotes = Emote::whereIn('id', $counts->keys())->get()->keyBy('id');

        $reactedEmoteIds = $currentUserId !== null
            ? $this->reactions()->where('user_id', $currentUserId)->whereIn('emote_id', $counts->keys())->pluck('emote_id')->all()
            : [];

        return $counts
            ->map(fn ($count, $emoteId) => [
                'emote' => $emotes[$emoteId],
                'count' => $count,
                'reacted' => in_array($emoteId, $reactedEmoteIds, true),
            ])
            ->values();
    }

    /**
     * Individual reactions for a single emote, with the reacting user eager
     * loaded — backs the "who reacted" panel gated by the reaction.view
     * permission (see resources/views/livewire/reaction-bar.blade.php),
     * unlike reactionSummary() which only aggregates counts.
     *
     * @return Collection<int, Reaction>
     */
    public function reactionsForEmote(int $emoteId): Collection
    {
        return $this->reactions()->with('user')->where('emote_id', $emoteId)->latest()->get();
    }
}
