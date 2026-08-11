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
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

trait HasLogo
{
    public function currentLogo(): MorphOne
    {
        return $this->morphOne(Logo::class, 'entity')->ofMany(['from' => 'max'], function ($query) {
            $query->whereNull('until')->whereNull('theme');
        });
    }

    public function currentLogoDark(): MorphOne
    {
        return $this->morphOne(Logo::class, 'entity')->ofMany(['from' => 'max'], function ($query) {
            $query->whereNull('until')->where('theme', 'dark');
        });
    }

    public function currentLogoLight(): MorphOne
    {
        return $this->morphOne(Logo::class, 'entity')->ofMany(['from' => 'max'], function ($query) {
            $query->whereNull('until')->where('theme', 'light');
        });
    }

    /**
     * Storage sub-folder the entity's logos are stored under (e.g. "teams",
     * "players"). Must be implemented by the using model.
     */
    abstract protected function logoStorageFolder(): string;

    /**
     * URL returned when the entity has no current logo. Must be implemented
     * by the using model. Receives the resolved theme ("dark"/"light"/null)
     * so entities with theme-scoped default artwork (e.g. Team's
     * default-team-light/dark.webp) can pick the matching variant.
     */
    abstract protected function defaultLogoUrl(?string $theme = null): string;

    /**
     * Resolves to the given theme's dedicated logo variant, falling back to
     * the theme-agnostic logo, then to defaultLogoUrl() when the entity has
     * no logo at all. Pass null (the default) for the theme-agnostic logo.
     */
    protected function resolveLogoUrl(?string $theme = null): string
    {
        $logo = match ($theme) {
            'dark' => $this->currentLogoDark ?? $this->currentLogo,
            'light' => $this->currentLogoLight ?? $this->currentLogo,
            default => $this->currentLogo,
        };

        if (! $logo) {
            return $this->defaultLogoUrl($theme);
        }

        return Storage::disk('public')->url("{$this->logoStorageFolder()}/{$logo->id}/200x200.webp");
    }

    /**
     * The logo this entity displayed at a given point in time — same idea
     * as Team::nameAt(), for match displays that should show the logo as it
     * was when the match was played rather than the current one. Reads from
     * the already-loaded `logos` collection when available (eager-load it
     * to avoid N+1 across a list of matches), skipping any entry an admin
     * marked `is_visible = false`. Falls back to defaultLogoUrl() — not the
     * live current logo — when nothing matches, since a caller resolving a
     * specific date wants that date's logo or nothing, not today's.
     */
    public function logoAt(CarbonInterface|string $date, ?string $theme = null): string
    {
        $date = $date instanceof CarbonInterface ? $date : Carbon::parse($date);

        $matches = $this->logos
            ->filter(fn (Logo $logo) => $logo->is_visible && $logo->from->lte($date) && (! $logo->until || $logo->until->gte($date)))
            ->sortByDesc('from');

        $logo = match ($theme) {
            'dark' => $matches->first(fn (Logo $logo) => $logo->theme === 'dark') ?? $matches->first(fn (Logo $logo) => $logo->theme === null),
            'light' => $matches->first(fn (Logo $logo) => $logo->theme === 'light') ?? $matches->first(fn (Logo $logo) => $logo->theme === null),
            default => $matches->first(fn (Logo $logo) => $logo->theme === null),
        };

        if (! $logo) {
            return $this->defaultLogoUrl($theme);
        }

        return Storage::disk('public')->url("{$this->logoStorageFolder()}/{$logo->id}/200x200.webp");
    }
}
