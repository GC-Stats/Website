<?php

/**
 * GC-Stats — Player profile service
 *
 * Updates a player's editable profile fields and logo. Mirrors
 * App\Services\TeamProfileService — see its docblock for why this is kept
 * as a standalone service rather than folded into the admin controller.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use App\Models\Player;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class PlayerProfileService
{
    public function __construct(private readonly LogoUploadService $logoUploadService) {}

    /**
     * Note: val_id (Riot ID) and discord_id are identity links established
     * by the player themselves (Riot/Discord OAuth), not free-text profile
     * fields — admins can only clear them, via resetValId()/resetDiscordId(),
     * never set or rewrite them, so this deliberately never touches either.
     *
     * @param  array{handle: string, first_name?: ?string, last_name?: ?string, country_code?: ?string, bio?: ?string, vlr_id?: ?int, liquipedia_link?: ?string, is_active?: bool, socials?: array}  $data
     */
    public function updateProfile(Player $player, array $data, User $actor): void
    {
        $player->update([
            'handle' => $data['handle'],
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'country_code' => $data['country_code'] ?? null,
            'bio' => $data['bio'] ?? null,
            'vlr_id' => $data['vlr_id'] ?? null,
            'liquipedia_link' => $data['liquipedia_link'] ?? null,
            'is_active' => $data['is_active'] ?? false,
            'socials' => array_filter($data['socials'] ?? [], fn ($value) => filled($value)),
        ]);

        activity('player')->performedOn($player)->causedBy($actor)->log('player.profile_updated');
    }

    /**
     * Unlike updateProfile(), this does rewrite val_id/discord_id directly —
     * gated at the route level behind the stricter 'players.identifiers.manage'
     * permission rather than the base 'players.edit'.
     *
     * @param  array{val_id?: ?string, discord_id?: ?string}  $data
     */
    public function updateIdentifiers(Player $player, array $data, User $actor): void
    {
        $player->update([
            'val_id' => $data['val_id'] ?? null,
            'discord_id' => $data['discord_id'] ?? null,
        ]);

        activity('player')->performedOn($player)->causedBy($actor)->log('player.identifiers_updated');
    }

    public function resetValId(Player $player, User $actor): void
    {
        $player->update(['val_id' => null]);

        activity('player')->performedOn($player)->causedBy($actor)->log('player.val_id_reset');
    }

    public function resetDiscordId(Player $player, User $actor): void
    {
        $player->update(['discord_id' => null]);

        activity('player')->performedOn($player)->causedBy($actor)->log('player.discord_id_reset');
    }

    public function updateLogo(Player $player, UploadedFile $file, User $actor): void
    {
        $uuid = $this->logoUploadService->storeLogoPair($file, 'players');
        $this->logoUploadService->acceptWithHistory($player, 'player', $uuid);

        activity('player')->performedOn($player)->causedBy($actor)->log('player.logo_updated');
    }

    public function addLogoHistoryEntry(Player $player, UploadedFile $file, string $from, string $until, User $actor): void
    {
        $uuid = $this->logoUploadService->storeLogoPair($file, 'players');
        $this->logoUploadService->acceptWithHistory($player, 'player', $uuid, $from, $until);

        activity('player')->performedOn($player)->causedBy($actor)->log('player.logo_history_added');
    }

    public function updateLogoEntry(Player $player, string $logoId, string $from, ?string $until, User $actor): void
    {
        $logo = $player->logos()->findOrFail($logoId);
        $logo->update(['from' => $from, 'until' => $until]);

        activity('player')->performedOn($player)->causedBy($actor)->log('player.logo_history_updated');
    }

    public function deleteLogoEntry(Player $player, string $logoId, User $actor): void
    {
        $logo = $player->logos()->findOrFail($logoId);
        $this->logoUploadService->deleteFiles('players', $logo->id);
        $logo->delete();

        activity('player')->performedOn($player)->causedBy($actor)->log('player.logo_history_removed');
    }
}
