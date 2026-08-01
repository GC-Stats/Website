<?php

/**
 * GC-Stats — FieldApplier contract
 *
 * Applies one accepted ChangeRequestItem's new_value onto its subject model.
 * Kept separate per field (rather than a generic `$model->update()`) so
 * fields backed by business logic beyond a plain column — e.g. a roster
 * change, which must go through RosterService to close out the player's
 * prior team — apply through the same code path as a manual admin edit.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services\ChangeRequests;

use App\Models\ChangeRequestItem;
use Illuminate\Database\Eloquent\Model;

interface FieldApplier
{
    /**
     * @param  Model  $subject  The change request's polymorphic subject.
     * @param  mixed  $newValue  The item's decoded new_value.
     */
    public function apply(Model $subject, mixed $newValue, ChangeRequestItem $item): void;
}
