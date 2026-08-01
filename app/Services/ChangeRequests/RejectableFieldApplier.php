<?php

/**
 * GC-Stats — RejectableFieldApplier contract
 *
 * Optional extension of FieldApplier for fields whose proposal has a side
 * effect that must be undone if a moderator rejects it instead of accepting
 * it — e.g. a photo change request that already wrote files to disk at
 * submission time (see PlayerPhotoApplier). ChangeRequestService::rejectItem()
 * calls onReject() best-effort when the resolved applier implements this.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services\ChangeRequests;

interface RejectableFieldApplier
{
    /**
     * @param  mixed  $newValue  The rejected item's decoded new_value.
     */
    public function onReject(mixed $newValue): void;
}
