<?php

/**
 * GC-Stats — ChangeRequestApplierRegistry
 *
 * Resolves the FieldApplier for a given change_requests.subject_type
 * (a Relation::morphMap alias, e.g. "team") + change_request_items.field
 * pair. Add an entry here whenever a new field becomes proposable through
 * the change request system.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services\ChangeRequests;

use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\ChangeRequests\Appliers\PlayerLinkUserApplier;
use App\Services\ChangeRequests\Appliers\PlayerPhotoApplier;
use App\Services\ChangeRequests\Appliers\PlayerRosterApplier;
use App\Services\ChangeRequests\Appliers\PlayerRosterHistoryApplier;
use App\Services\ChangeRequests\Appliers\SimpleAttributeApplier;
use App\Services\ChangeRequests\Appliers\TeamLogoApplier;
use App\Services\ChangeRequests\Appliers\TeamRosterAddApplier;
use App\Services\ChangeRequests\Appliers\TeamRosterHistoryApplier;
use App\Services\LogoUploadService;
use App\Services\PlayerProfileService;
use App\Services\RosterService;
use InvalidArgumentException;

class ChangeRequestApplierRegistry
{
    public function __construct(
        private readonly RosterService $roster,
        private readonly PlayerProfileService $playerProfiles,
        private readonly LogoUploadService $logoUploadService,
    ) {}

    /**
     * @return array<class-string, array<string, FieldApplier>>
     */
    private function map(): array
    {
        return [
            Team::class => [
                'name' => new SimpleAttributeApplier('name'),
                'short_name' => new SimpleAttributeApplier('short_name'),
                'bio' => new SimpleAttributeApplier('bio'),
                'country_code' => new SimpleAttributeApplier('country_code'),
                'liquipedia_link' => new SimpleAttributeApplier('liquipedia_link'),
                'vlr_id' => new SimpleAttributeApplier('vlr_id'),
                'socials' => new SimpleAttributeApplier('socials'),
            ],
            Player::class => [
                'first_name' => new SimpleAttributeApplier('first_name'),
                'last_name' => new SimpleAttributeApplier('last_name'),
                'country_code' => new SimpleAttributeApplier('country_code'),
                'bio' => new SimpleAttributeApplier('bio'),
                'socials' => new SimpleAttributeApplier('socials'),
                'roster' => new PlayerRosterApplier($this->roster),
            ],
            Tournament::class => [
                'name' => new SimpleAttributeApplier('name'),
                'region' => new SimpleAttributeApplier('region'),
                'location' => new SimpleAttributeApplier('location'),
                'prize_pool' => new SimpleAttributeApplier('prize_pool'),
                'description' => new SimpleAttributeApplier('description'),
                'liquipedia_link' => new SimpleAttributeApplier('liquipedia_link'),
            ],
        ];
    }

    /**
     * Appliers resolvable but deliberately left out of fieldsFor()'s generic
     * field pickers (admin's manual create form) — they need a shaped
     * payload only a dedicated flow builds, e.g. link_user's {user_id: int}
     * built by PlayerChangeRequestController, not a free-text admin field.
     *
     * @return array<class-string, array<string, FieldApplier>>
     */
    private function hiddenMap(): array
    {
        return [
            Player::class => [
                'link_user' => new PlayerLinkUserApplier($this->playerProfiles),
                'photo' => new PlayerPhotoApplier($this->playerProfiles, $this->logoUploadService),
                'roster_history' => new PlayerRosterHistoryApplier($this->roster),
            ],
            Team::class => [
                'logo' => new TeamLogoApplier($this->logoUploadService),
                'roster_add' => new TeamRosterAddApplier($this->roster),
                'roster_history' => new TeamRosterHistoryApplier($this->roster),
            ],
        ];
    }

    public function resolve(string $subjectClass, string $field): FieldApplier
    {
        $applier = $this->map()[$subjectClass][$field] ?? $this->hiddenMap()[$subjectClass][$field] ?? null;

        if (! $applier) {
            throw new InvalidArgumentException("No FieldApplier registered for {$subjectClass}::{$field}.");
        }

        return $applier;
    }

    /**
     * @return list<string>
     */
    public function fieldsFor(string $subjectClass): array
    {
        return array_keys($this->map()[$subjectClass] ?? []);
    }
}
