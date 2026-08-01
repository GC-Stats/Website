<?php

/**
 * GC-Stats — Player API controller
 *
 * Exposes a JSON endpoint returning a player's profile information
 * (handle, name, country, bio, photo, socials, current teams).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Public\Controller;
use App\Models\Player;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiPlayerController extends Controller
{
    public function show(int $id): JsonResponse
    {
        $player = Player::with([
            'teams' => fn ($q) => $q->wherePivot('left_at', null)
                ->select('teams.id', 'teams.name', 'teams.short_name'),
        ])->findOrFail($id);

        return response()->json([
            'id' => $player->id,
            'handle' => $player->handle,
            'first_name' => $player->first_name,
            'last_name' => $player->last_name,
            'country_code' => $player->country_code,
            'bio' => $player->bio,
            'photo' => $player->profile_photo,
            'socials' => $player->socials,
            'vlr_id' => $player->vlr_id,
            'liquipedia_link' => $player->liquipedia_link,
            'discord_id' => $player->discord_id,
            'team' => $player->teams->first() ? [
                'id' => $player->teams->first()->id,
                'name' => $player->teams->first()->name,
                'short_name' => $player->teams->first()->short_name,
            ] : null,
        ]);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $player = Player::findOrFail($id);

        $validated = $request->validate([
            'handle' => ['sometimes', 'string', 'max:50'],
            'first_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'country_code' => ['sometimes', 'nullable', 'string', 'size:2'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'socials' => ['sometimes', 'array'],
            'socials.*' => ['nullable', 'string', 'max:255'],
            'discord_id' => ['sometimes', 'nullable', 'string', 'max:20'],
            'vlr_id' => ['sometimes', 'nullable', 'integer', 'max:99999999'],
            'liquipedia_link' => ['sometimes', 'nullable', 'url', 'max:255'],
        ]);

        $player->update($validated);

        return response()->json([
            'success' => true,
            'player' => $player->fresh(),
        ]);
    }
}
