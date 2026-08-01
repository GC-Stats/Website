<?php

/**
 * GC-Stats — SimpleAttributeApplier
 *
 * Generic FieldApplier for a plain scalar/array column: sets the attribute
 * and saves. Used for fields with no business logic beyond assignment (e.g.
 * a team's name or bio) — anything with an invariant to enforce needs its
 * own applier instead.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services\ChangeRequests\Appliers;

use App\Models\ChangeRequestItem;
use App\Services\ChangeRequests\FieldApplier;
use Illuminate\Database\Eloquent\Model;

class SimpleAttributeApplier implements FieldApplier
{
    public function __construct(private readonly string $attribute) {}

    public function apply(Model $subject, mixed $newValue, ChangeRequestItem $item): void
    {
        $subject->{$this->attribute} = $newValue;
        $subject->save();
    }
}
