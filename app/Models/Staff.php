<?php

/**
 * GC-Stats — Staff model
 *
 * Represents a non-player esports staff member (coach, analyst, manager,
 * caster, observer, etc.) — the counterpart to Player for roles that don't
 * play. Staff members hold roster history on organizations
 * (organizations()/staffOrganizations()) and, with no organization
 * involved, directly on teams (teams()/staffTeams()) — a team has no
 * organization of its own, so staff working for it directly link there
 * instead. Individual participation declarations on tournaments/matches/
 * teams are tracked separately (assignments()).
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
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Staff extends Model
{
    use HasFactory, HasLogo;

    protected $table = 'staff';

    protected $appends = ['photo'];

    protected $fillable = [
        'user_id',
        'player_id',
        'handle',
        'first_name',
        'last_name',
        'country_code',
        'pronouns',
        'bio',
        'vlr_id',
        'socials',
        'is_active',
        'liquipedia_link',
    ];

    protected $casts = [
        'socials' => 'array',
        'pronouns' => 'integer',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The player profile this staff member is also active as, if any — same real person, both roles. */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /** Roster history across organizations — see StaffOrganization. A staff member can hold several active memberships at once. */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'staff_organizations')
            ->withPivot('role', 'joined_at', 'left_at')
            ->orderBy('joined_at', 'desc')
            ->withTimestamps();
    }

    public function currentOrganizations(): BelongsToMany
    {
        return $this->organizations()->wherePivot('left_at', null);
    }

    public function staffOrganizations(): HasMany
    {
        return $this->hasMany(StaffOrganization::class);
    }

    /** Direct team affiliations (no organization involved) — see StaffTeam. Same "several active at once" rule as organizations(). */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'staff_teams')
            ->withPivot('role', 'joined_at', 'left_at')
            ->orderBy('joined_at', 'desc')
            ->withTimestamps();
    }

    public function currentTeams(): BelongsToMany
    {
        return $this->teams()->wherePivot('left_at', null);
    }

    public function staffTeams(): HasMany
    {
        return $this->hasMany(StaffTeam::class);
    }

    /** Declared participations (tournament/match/team) — see StaffAssignment. */
    public function assignments(): HasMany
    {
        return $this->hasMany(StaffAssignment::class);
    }

    public function news()
    {
        return $this->morphToMany(News::class, 'relationable', 'news_relations', 'relationable_id', 'news_id');
    }

    public function logos(): MorphMany
    {
        return $this->morphMany(Logo::class, 'entity');
    }

    /** Every qualification/placement this staff member has ever satisfied — see PhaseQualificationResult. */
    public function qualificationResults(): MorphMany
    {
        return $this->morphMany(PhaseQualificationResult::class, 'entity');
    }

    public function getPhotoAttribute(): string
    {
        return $this->resolveLogoUrl();
    }

    protected function logoStorageFolder(): string
    {
        return 'staff';
    }

    protected function defaultLogoUrl(?string $theme = null): string
    {
        return '';
    }
}
