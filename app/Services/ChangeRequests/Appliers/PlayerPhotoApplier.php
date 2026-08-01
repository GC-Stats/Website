<?php

/**
 * GC-Stats — PlayerPhotoApplier
 *
 * FieldApplier for a Player's `photo` field: links an already-uploaded logo
 * pair to the player via PlayerProfileService::applyPendingPhoto(). The
 * image itself is uploaded and written to disk by
 * PlayerChangeRequestController at submission time (see LogoUploadService::
 * storeLogoPair()) — moderation only gates whether it ever gets attached to
 * the player, not whether it gets stored. Implements RejectableFieldApplier
 * so a rejected proposal deletes the orphaned files instead of leaving them
 * on disk forever.
 *
 * new_value shape: {"logo_id": string (uuid)}.
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
use App\Services\ChangeRequests\RejectableFieldApplier;
use App\Services\LogoUploadService;
use App\Services\PlayerProfileService;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use RuntimeException;

class PlayerPhotoApplier implements FieldApplier, RejectableFieldApplier
{
    public function __construct(
        private readonly PlayerProfileService $playerProfiles,
        private readonly LogoUploadService $logoUploadService,
    ) {}

    public function apply(Model $subject, mixed $newValue, ChangeRequestItem $item): void
    {
        if (! $subject instanceof Player) {
            throw new InvalidArgumentException('PlayerPhotoApplier requires a Player subject.');
        }

        if (! isset($newValue['logo_id'])) {
            throw new InvalidArgumentException('photo new_value must include logo_id.');
        }

        $actor = $item->resolvedBy;

        if (! $actor) {
            throw new RuntimeException('photo requires a resolving moderator.');
        }

        $this->playerProfiles->applyPendingPhoto($subject, $newValue['logo_id'], $actor);
    }

    public function onReject(mixed $newValue): void
    {
        if (isset($newValue['logo_id'])) {
            $this->logoUploadService->deleteFiles('players', $newValue['logo_id']);
        }
    }
}
