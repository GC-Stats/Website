<?php

/**
 * GC-Stats — Staff role catalog
 *
 * Two fixed lists of roles a Staff member can hold: TEAM_ROLES for a roster
 * spot on a Team (staff_teams.role) or a team-represented XP entry
 * (staff_assignments.role when team_id is set), and ORG_ROLES for an
 * organization membership (staff_organizations.role) or an org-represented/
 * org-held XP entry (staff_assignments.role when organization_id is set).
 * Purely a display/labeling list (see lang/{locale}/staff.php for localized
 * labels), not enforced by any DB constraint, so it can grow freely without
 * a migration.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Support;

class StaffRoles
{
    public const TEAM_ROLES = [
        'coach',
        'assistant coach',
        'performance coach',
        'team manager',
    ];

    public const ORG_ROLES = [
        'manager',
        'president',
        'ceo',
        'vice president',
        'treasurer',
        'owner',
        'general manager',
        'talent manager',
        'content manager',
        'community manager',
        'social media manager',
        'graphic designer',
        'video editor',
        'photographer',
        'web developer',
        'tournament organizer',
        'caster',
        'observer',
        'host',
        'production',
        'producer',
        'director',
        'analyst',
        'partnerships manager',
        'marketing manager',
        'hr',
        'finance',
    ];

    /** Every role, team + org combined — for contexts that don't distinguish (e.g. sorting a mixed list). */
    public const ALL_ROLES = [...self::TEAM_ROLES, ...self::ORG_ROLES];
}
