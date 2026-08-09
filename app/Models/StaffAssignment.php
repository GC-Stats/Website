<?php

/**
 * GC-Stats — Staff experience (XP) entry model
 *
 * A single declared XP entry: "this was done on this tournament/match,
 * representing this team or org" — `assignable` is polymorphic and expected
 * to be a Tournament or a Matchs (see the app-wide Relation::morphMap in
 * AppServiceProvider; Team is no longer a valid assignable — team
 * affiliation is already covered by staff_teams/staff_organizations
 * rosters). Any pre-existing assignable_type = 'team' rows are legacy and
 * excluded from new queries.
 *
 * Exactly one of `team_id`/`organization_id` is required — which team or
 * org this entry represents, and which role list (StaffRoles::TEAM_ROLES vs
 * ::ORG_ROLES) the `role` value is validated against.
 *
 * `staff_id` is nullable: when set, this is a staff member's personal XP
 * entry (they represented the linked team/org). When null, `organization_id`
 * is required and doubles as the entry's *holder* — this is the
 * organization's own XP (e.g. "Org X organized Tournament Y"), shown on the
 * org's own public profile rather than any individual's. `team_id` must stay
 * null in that case — orgs don't hold XP "as a team".
 *
 * No stored dates — the displayed date is derived from the linked
 * tournament's start_date (see tournamentStartDate()), since manually typed
 * dates drifted from the actual event date in practice.
 *
 * `metadata` is a free-form JSON bag for role-specific extras (e.g. a
 * caster's broadcast language) — no fixed schema, extend as needed without
 * a migration.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StaffAssignment extends Model
{
    protected $table = 'staff_assignments';

    /** Valid `assignable_type` values going forward — Team is legacy, see class docblock. */
    public const ASSIGNABLE_TYPES = ['tournament', 'match'];

    protected $fillable = [
        'staff_id',
        'assignable_type',
        'assignable_id',
        'team_id',
        'organization_id',
        'role',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function assignable(): MorphTo
    {
        return $this->morphTo();
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** True when this XP entry is held by the organization itself rather than a staff member. */
    public function isOrgHeld(): bool
    {
        return $this->staff_id === null;
    }

    /**
     * Org-held entries only (staff_id null) — organization_id is the entry's
     * *holder* here, not "org a staff member represented". See this class's
     * docblock for the organization_id overload this scope disambiguates.
     */
    public function scopeOrgHeld($query)
    {
        return $query->whereNull('staff_id');
    }

    /**
     * Staff-held entries representing the given organization (staff_id set,
     * organization_id names which org they represented) — the complement of
     * scopeOrgHeld(), see this class's docblock.
     */
    public function scopeRepresentingOrganization($query, int $organizationId)
    {
        return $query->whereNotNull('staff_id')->where('organization_id', $organizationId);
    }

    /**
     * The date this entry displays as — always derived from the linked
     * tournament's start_date, never stored: directly for a tournament-level
     * entry, via the match's own tournament for a match-level one.
     */
    public function tournamentStartDate(): ?CarbonInterface
    {
        return match ($this->assignable_type) {
            'tournament' => $this->assignable?->start_date,
            'match' => $this->assignable?->tournament?->start_date,
            default => null,
        };
    }
}
