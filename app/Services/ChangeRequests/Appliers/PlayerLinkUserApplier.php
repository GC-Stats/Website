<?php

/**
 * GC-Stats — PlayerLinkUserApplier
 *
 * FieldApplier for a Player's `link_user` field: links the proposing user's
 * account to the player via PlayerProfileService::linkUser(), the same
 * primitive the admin player edit page uses. Refuses to silently overwrite
 * an existing link — players.user_id is unique per the migration, so
 * re-linking over a different account needs an admin to unlink first,
 * rather than this applier picking a winner.
 *
 * new_value shape: {"user_id": int}.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services\ChangeRequests\Appliers;

use App\Models\ChangeRequestItem;
use App\Models\Player;
use App\Services\ChangeRequests\FieldApplier;
use App\Services\PlayerProfileService;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use RuntimeException;

class PlayerLinkUserApplier implements FieldApplier
{
    public function __construct(private readonly PlayerProfileService $playerProfiles) {}

    public function apply(Model $subject, mixed $newValue, ChangeRequestItem $item): void
    {
        if (! $subject instanceof Player) {
            throw new InvalidArgumentException('PlayerLinkUserApplier requires a Player subject.');
        }

        if (! isset($newValue['user_id'])) {
            throw new InvalidArgumentException('link_user new_value must include user_id.');
        }

        $userId = (int) $newValue['user_id'];

        if ($subject->user_id !== null && $subject->user_id !== $userId) {
            throw new RuntimeException('This player is already linked to a different account — unlink it first.');
        }

        if (Player::where('user_id', $userId)->where('id', '!=', $subject->id)->exists()) {
            throw new RuntimeException('That account is already linked to another player.');
        }

        $actor = $item->resolvedBy;

        if (! $actor) {
            throw new RuntimeException('link_user requires a resolving moderator.');
        }

        $this->playerProfiles->linkUser($subject, $userId, $actor);
    }
}
