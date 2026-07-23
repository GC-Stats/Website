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

trait HasReactions
{
    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactable');
    }

    /**
     * Reactions grouped by emote, most-reacted first. Aggregates counts in
     * SQL rather than loading every reaction row — this scales with the
     * number of distinct emotes used, not the (potentially much larger)
     * total reaction count.
     *
     * @return Collection<int, array{emote: Emote, count: int, reacted: bool}>
     */
    public function reactionSummary(?int $currentUserId = null): Collection
    {
        $counts = $this->reactions()
            ->selectRaw('emote_id, count(*) as count')
            ->groupBy('emote_id')
            ->orderByDesc('count')
            ->pluck('count', 'emote_id');

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
}
