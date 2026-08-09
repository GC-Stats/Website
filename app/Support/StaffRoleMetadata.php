<?php

/**
 * GC-Stats — Staff experience metadata schema
 *
 * StaffAssignment::$metadata is a free-form JSON bag, but only one role
 * currently has a real use for it: a caster's broadcast language. Every
 * other role gets no metadata field at all in the admin/org editors, and
 * StaffAssignmentService::save() strips any submitted metadata for roles
 * not listed in ROLES_WITH_METADATA — the single choke point every sync
 * path (Admin\StaffController, Admin\TournamentController,
 * Admin\MatchController, Organization\ExperienceController) runs through,
 * so a stray client-side value can't leak metadata onto an unrelated role.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Support;

class StaffRoleMetadata
{
    /** Roles whose XP entries carry a structured `metadata` field. */
    public const ROLES_WITH_METADATA = ['caster'];

    /** ISO 639-1 code => English language name, sorted by name — caster.metadata.language options. */
    public const LANGUAGES = [
        'ar' => 'Arabic',
        'zh' => 'Chinese',
        'en' => 'English',
        'fr' => 'French',
        'de' => 'German',
        'id' => 'Indonesian',
        'it' => 'Italian',
        'ja' => 'Japanese',
        'ko' => 'Korean',
        'pl' => 'Polish',
        'pt' => 'Portuguese',
        'ru' => 'Russian',
        'es' => 'Spanish',
        'th' => 'Thai',
        'tr' => 'Turkish',
        'vi' => 'Vietnamese',
    ];

    public static function hasMetadata(?string $role): bool
    {
        return in_array($role, self::ROLES_WITH_METADATA, true);
    }
}
