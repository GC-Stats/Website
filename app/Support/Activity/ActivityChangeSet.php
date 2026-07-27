<?php

/**
 * GC-Stats — Activity log change set builder
 *
 * Builds the `changes` payload stored in an activity's `properties` JSON
 * column: a field => {old, new} map describing exactly what was modified.
 * fromModel() reads Eloquent's own dirty-tracking so callers don't have to
 * snapshot values by hand before an update(). Note: it deliberately uses
 * wasChanged()/getPrevious() rather than getOriginal() — save() calls
 * syncOriginal() internally, so by the time fromModel() runs (after the
 * caller's ->update() has already returned), getOriginal() reflects the
 * NEW value too. getPrevious() is the post-save snapshot of what the
 * original values were, taken by Eloquent before that sync happens.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Support\Activity;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;

class ActivityChangeSet implements Arrayable
{
    /** @var array<string, array{old: mixed, new: mixed}> */
    private array $changes = [];

    public static function make(): self
    {
        return new self;
    }

    /**
     * @param  list<string>  $attributes  attributes to diff, must have already been saved
     */
    public static function fromModel(Model $model, array $attributes): self
    {
        $set = new self;

        // getPrevious() returns raw (uncast) values, so route them through a
        // throwaway instance of the same model to apply its casts, keeping
        // old/new formatted the same way for the activity log UI.
        $previousModel = (new ($model::class))->setRawAttributes($model->getPrevious(), true);

        foreach ($attributes as $attribute) {
            if ($model->wasChanged($attribute)) {
                $set->add($attribute, $previousModel->getAttribute($attribute), $model->getAttribute($attribute));
            }
        }

        return $set;
    }

    /**
     * @param  list<string>  $attributes  attributes to record as newly set (no prior value)
     */
    public static function fromCreated(Model $model, array $attributes): self
    {
        $set = new self;

        foreach ($attributes as $attribute) {
            $set->add($attribute, null, $model->getAttribute($attribute));
        }

        return $set;
    }

    public function add(string $field, mixed $old, mixed $new): static
    {
        if ($old !== $new) {
            $this->changes[$field] = ['old' => $old, 'new' => $new];
        }

        return $this;
    }

    public function isEmpty(): bool
    {
        return $this->changes === [];
    }

    /**
     * @return array{changes: array<string, array{old: mixed, new: mixed}>}
     */
    public function toArray(): array
    {
        return ['changes' => $this->changes];
    }

    /**
     * Merge this change set into an existing properties array, e.g.
     * ActivityChangeSet::fromModel(...)->mergeInto(['team_id' => $team->id]).
     *
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    public function mergeInto(array $properties): array
    {
        return $this->isEmpty() ? $properties : [...$properties, ...$this->toArray()];
    }
}
