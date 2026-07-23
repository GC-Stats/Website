<?php

/**
 * GC-Stats — Team profile service
 *
 * Updates a team's editable profile fields and logo. Deliberately kept
 * independent of who's calling it (a team owner today, gated by
 * team.profile.edit/team.logo.upload — a site editor from the admin panel
 * later) so both share this one implementation rather than duplicating it.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class TeamProfileService
{
    public function __construct(private readonly LogoUploadService $logoUploadService) {}

    /**
     * Note: there is no standalone `website` column on `teams` — a team's
     * website lives inside `socials['website']` alongside its other social
     * links (see resources/views/team/header.blade.php's $socialConfig).
     *
     * @param  array{name: string, short_name?: ?string, country_code?: ?string, bio?: ?string, vlr_id?: ?int, liquipedia_link?: ?string, socials?: array}  $data
     */
    public function updateProfile(Team $team, array $data, User $actor): void
    {
        $team->update([
            'name' => $data['name'],
            'short_name' => $data['short_name'] ?? null,
            'country_code' => $data['country_code'] ?? null,
            'bio' => $data['bio'] ?? null,
            'vlr_id' => $data['vlr_id'] ?? null,
            'liquipedia_link' => $data['liquipedia_link'] ?? null,
            'socials' => array_filter($data['socials'] ?? [], fn ($value) => filled($value)),
        ]);

        if ($team->wasChanged(['name', 'short_name', 'country_code', 'bio', 'vlr_id', 'liquipedia_link'])) {
            activity('team')->performedOn($team)->causedBy($actor)->log('team.information_updated');
        }

        if ($team->wasChanged('socials')) {
            activity('team')->performedOn($team)->causedBy($actor)->log('team.socials_updated');
        }
    }

    /**
     * @param  list<string>  $tags
     */
    public function updateTags(Team $team, array $tags, User $actor): void
    {
        $newTags = array_values(array_filter(array_map('trim', $tags), fn ($tag) => $tag !== ''));

        $team->update(['tags' => $newTags]);

        if ($team->wasChanged('tags')) {
            activity('team')->performedOn($team)->causedBy($actor)->log('team.tags_updated');

            // A user's fan tag (App\Models\User::team_tag) is only ever
            // validated against this list at pick time (see
            // Auth\AccountSettingsController::updateFanTeam) — without this,
            // removing a tag here would leave it permanently displayed on
            // any profile that had already picked it.
            User::where('team_id', $team->id)
                ->whereNotIn('team_tag', $newTags)
                ->whereNotNull('team_tag')
                ->update(['team_tag' => null]);
        }
    }

    public function updateLogo(Team $team, UploadedFile $file, User $actor): void
    {
        $uuid = $this->logoUploadService->storeLogoPair($file, 'teams');
        $this->logoUploadService->acceptWithHistory($team, 'team', $uuid);

        activity('team')->performedOn($team)->causedBy($actor)->log('team.logo_updated');
    }

    public function addLogoHistoryEntry(Team $team, UploadedFile $file, string $from, string $until, User $actor): void
    {
        $uuid = $this->logoUploadService->storeLogoPair($file, 'teams');
        $this->logoUploadService->acceptWithHistory($team, 'team', $uuid, $from, $until);

        activity('team')->performedOn($team)->causedBy($actor)->log('team.logo_history_added');
    }

    public function updateLogoEntry(Team $team, string $logoId, string $from, ?string $until, User $actor): void
    {
        $logo = $team->logos()->findOrFail($logoId);
        $logo->update(['from' => $from, 'until' => $until]);

        activity('team')->performedOn($team)->causedBy($actor)->log('team.logo_history_updated');
    }

    public function deleteLogoEntry(Team $team, string $logoId, User $actor): void
    {
        $logo = $team->logos()->findOrFail($logoId);
        $this->logoUploadService->deleteFiles('teams', $logo->id);
        $logo->delete();

        activity('team')->performedOn($team)->causedBy($actor)->log('team.logo_history_removed');
    }
}
