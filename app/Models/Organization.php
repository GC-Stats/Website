<?php

/**
 * GC-Stats — Organization model
 *
 * Represents a pure-staff organization (talent agency, media outlet,
 * broadcast production, esports org handling staff, etc.) — the
 * counterpart to Team for entities that don't field a player roster. Can
 * exist on the site without any user attached (like Team), and shows off
 * its staff roster and (later) its point ledger. Also the entity News,
 * StreamChannel and Vod are attributed to, replacing the removed
 * NewsPublisher concept (see the 0122 migration). Its own roles are scoped
 * through App\Support\OrganizationPermissions on the 'organization' guard,
 * ceiling-capped by max_permissions (set by a site admin).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Models;

use App\Models\Concerns\HasLogo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Organization extends Model
{
    use HasFactory, HasLogo;

    protected $table = 'organization';

    protected $appends = ['logo'];

    protected $fillable = [
        'name',
        'slug',
        'types',
        'country_code',
        'liquipedia_link',
        'socials',
        'max_permissions',
    ];

    protected $casts = [
        'types' => 'array',
        'socials' => 'array',
        'max_permissions' => 'array',
    ];

    /**
     * The ceiling of App\Support\OrganizationPermissions this organization's
     * own roles can ever be granted, set by a site admin.
     *
     * @return list<string>
     */
    public function maxPermissions(): array
    {
        return $this->max_permissions ?? [];
    }

    /**
     * What this organization does (e.g. "media", "production", "talent
     * agency") — purely descriptive, shown on its public profile. An
     * organization can carry several at once.
     *
     * @return list<string>
     */
    public function types(): array
    {
        return $this->types ?? [];
    }

    /**
     * SEO-friendly URL segment for this organization — not stored, derived
     * from the name (falls back to the id for names with no Latin-
     * transliterable characters), matching Team::routeSlug().
     */
    public function routeSlug(): string
    {
        return Str::routeSlug($this->name, $this->id);
    }

    /** Roster history across staff members — see StaffOrganization. */
    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'staff_organizations')
            ->withPivot('role', 'joined_at', 'left_at')
            ->withTimestamps();
    }

    public function currentStaff(): BelongsToMany
    {
        return $this->staff()->wherePivot('left_at', null);
    }

    public function staffOrganizations(): HasMany
    {
        return $this->hasMany(StaffOrganization::class);
    }

    /** This organization's own XP entries (e.g. "this org organized Tournament Y") — see StaffAssignment::isOrgHeld(). */
    public function ownXpEntries(): HasMany
    {
        return $this->hasMany(StaffAssignment::class)->orgHeld();
    }

    /** XP entries where a staff member represented this organization — see StaffAssignment. */
    public function staffXpEntries(): HasMany
    {
        return $this->hasMany(StaffAssignment::class)->whereNotNull('staff_id');
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    /** Articles this organization is tagged on — distinct from News::organization_id (who published it). */
    public function news()
    {
        return $this->morphToMany(News::class, 'relationable', 'news_relations', 'relationable_id', 'news_id');
    }

    public function logos(): MorphMany
    {
        return $this->morphMany(Logo::class, 'entity');
    }

    /** Every qualification/placement this organization has ever satisfied — see PhaseQualificationResult. */
    public function qualificationResults(): MorphMany
    {
        return $this->morphMany(PhaseQualificationResult::class, 'entity');
    }

    public function getLogoAttribute(): string
    {
        return $this->resolveLogoUrl();
    }

    protected function logoStorageFolder(): string
    {
        return 'organizations';
    }

    protected function defaultLogoUrl(?string $theme = null): string
    {
        return '';
    }
}
