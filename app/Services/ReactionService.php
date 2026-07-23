<?php

/**
 * GC-Stats — Reaction service
 *
 * Toggles a user's emote reaction on a reactable model (see
 * App\Models\Concerns\HasReactions) — clicking the same emote twice
 * removes it instead of creating a duplicate row.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use App\Models\Concerns\HasReactions;
use App\Models\Emote;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ReactionService
{
    /**
     * @param  Model&HasReactions  $reactable
     * @return bool true if the reaction was added, false if it was removed
     */
    public function toggle(Model $reactable, User $user, Emote $emote): bool
    {
        $existing = $reactable->reactions()
            ->where('user_id', $user->id)
            ->where('emote_id', $emote->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        $reactable->reactions()->create([
            'user_id' => $user->id,
            'emote_id' => $emote->id,
        ]);

        return true;
    }
}
