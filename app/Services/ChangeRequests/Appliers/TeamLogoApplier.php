<?php

/**
 * GC-Stats — TeamLogoApplier
 *
 * FieldApplier for a Team's `logo` field: links an already-uploaded logo
 * pair to the team via LogoUploadService::acceptWithHistory(). The image
 * itself is uploaded and written to disk by TeamChangeRequestController at
 * submission time (see LogoUploadService::storeLogoPair()) — moderation
 * only gates whether it ever gets attached to the team, not whether it gets
 * stored. Implements RejectableFieldApplier so a rejected proposal deletes
 * the orphaned files instead of leaving them on disk forever. Mirrors
 * App\Services\ChangeRequests\Appliers\PlayerPhotoApplier.
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
use App\Models\Team;
use App\Services\ChangeRequests\FieldApplier;
use App\Services\ChangeRequests\RejectableFieldApplier;
use App\Services\LogoUploadService;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class TeamLogoApplier implements FieldApplier, RejectableFieldApplier
{
    public function __construct(private readonly LogoUploadService $logoUploadService) {}

    public function apply(Model $subject, mixed $newValue, ChangeRequestItem $item): void
    {
        if (! $subject instanceof Team) {
            throw new InvalidArgumentException('TeamLogoApplier requires a Team subject.');
        }

        if (! isset($newValue['logo_id'])) {
            throw new InvalidArgumentException('logo new_value must include logo_id.');
        }

        $this->logoUploadService->acceptWithHistory($subject, 'team', $newValue['logo_id']);
    }

    public function onReject(mixed $newValue): void
    {
        if (isset($newValue['logo_id'])) {
            $this->logoUploadService->deleteFiles('teams', $newValue['logo_id']);
        }
    }
}
