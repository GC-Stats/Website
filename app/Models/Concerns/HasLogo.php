<?php

/**
 * GC-Stats — Has logo trait
 *
 * Shared logic for entities (teams, tournaments, players, news authors/
 * publishers) that carry a time-scoped logo via the polymorphic Logo model.
 * Provides the currentLogo() relation (using latestOfMany() consistently —
 * previously NewsAuthor/NewsPublisher incorrectly used latest() instead,
 * which orders in PHP/SQL differently and doesn't benefit from the
 * "latest of many" subquery optimization) and a generic Storage URL
 * accessor built from each model's logo folder/default URL.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Models\Concerns;

use App\Models\Logo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Storage;

trait HasLogo
{
    public function currentLogo(): MorphOne
    {
        return $this->morphOne(Logo::class, 'entity')->whereNull('until')->latestOfMany('from');
    }

    /**
     * Storage sub-folder the entity's logos are stored under (e.g. "teams",
     * "players"). Must be implemented by the using model.
     */
    abstract protected function logoStorageFolder(): string;

    /**
     * URL returned when the entity has no current logo. Must be implemented
     * by the using model.
     */
    abstract protected function defaultLogoUrl(): string;

    protected function resolveLogoUrl(): string
    {
        $logo = $this->currentLogo;

        if (! $logo) {
            return $this->defaultLogoUrl();
        }

        return Storage::disk('public')->url("{$this->logoStorageFolder()}/{$logo->id}/200x200.webp");
    }
}
