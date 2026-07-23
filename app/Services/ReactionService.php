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
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

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
            $this->logToggle($reactable, $user, $emote, 'removed');

            return false;
        }

        $reactable->reactions()->create([
            'user_id' => $user->id,
            'emote_id' => $emote->id,
        ]);
        $this->logToggle($reactable, $user, $emote, 'added');

        return true;
    }

    /**
     * Add/remove-reaction events are logged, but a user flipping the same
     * emote on and off would otherwise flood the activity log with one row
     * per toggle — so repeated toggles by the same user on the same
     * reactable update a single running log entry instead of creating a new
     * one each time.
     */
    private function logToggle(Model $reactable, User $user, Emote $emote, string $action): void
    {
        $activity = Activity::query()
            ->where('log_name', 'reactions')
            ->where('subject_type', $reactable->getMorphClass())
            ->where('subject_id', $reactable->getKey())
            ->where('causer_type', $user->getMorphClass())
            ->where('causer_id', $user->getKey())
            ->where('properties->emote_id', $emote->id)
            ->latest('id')
            ->first();

        if ($activity) {
            $properties = $activity->properties->toArray();

            $activity->update([
                'description' => 'reaction.toggled',
                'properties' => [
                    ...$properties,
                    'emote_id' => $emote->id,
                    'action' => $action,
                    'toggles' => ($properties['toggles'] ?? 1) + 1,
                ],
                'updated_at' => now(),
            ]);

            return;
        }

        activity('reactions')
            ->performedOn($reactable)
            ->causedBy($user)
            ->withProperties(['emote_id' => $emote->id, 'action' => $action, 'toggles' => 1])
            ->log('reaction.toggled');
    }

    /**
     * Force-remove another user's reaction — used by moderators/admins via
     * the reaction.delete permission, unlike toggle() which only ever
     * affects the acting user's own reaction.
     */
    public function remove(Reaction $reaction): void
    {
        $reaction->delete();
    }

    /**
     * Wipe every reaction of one emote on a reactable — e.g. clearing a
     * brigaded/inappropriate emote in one action instead of deleting each
     * reactor one by one.
     *
     * @param  Model&HasReactions  $reactable
     */
    public function removeAllForEmote(Model $reactable, Emote $emote): void
    {
        $reactable->reactions()->where('emote_id', $emote->id)->delete();
    }
}
