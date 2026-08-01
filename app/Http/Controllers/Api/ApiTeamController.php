<?php

/**
 * GC-Stats — Team API controller
 *
 * Exposes a JSON endpoint returning a team's profile information
 * (name, country, region, bio, logo, socials, current roster).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Public\Controller;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiTeamController extends Controller
{
    public function show(int $id): JsonResponse
    {
        $team = Team::with([
            'currentPlayers:id,handle,first_name,last_name,country_code',
        ])->findOrFail($id);

        return response()->json([
            'id' => $team->id,
            'name' => $team->name,
            'short_name' => $team->short_name,
            'country_code' => $team->country_code,
            'region' => $team->region,
            'bio' => $team->bio,
            'logo' => $team->logo,
            'vlr_id' => $team->vlr_id,
            'liquipedia_link' => $team->liquipedia_link,
            'socials' => $team->socials,
            'roster_ids' => $team->currentPlayers->pluck('id'),
            'players' => $team->currentPlayers->map(fn ($p) => [
                'id' => $p->id,
                'handle' => $p->handle,
                'first_name' => $p->first_name,
                'last_name' => $p->last_name,
                'country_code' => $p->country_code,
            ]),
        ]);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $team = Team::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'short_name' => ['sometimes', 'nullable', 'string', 'max:20'],
            'country_code' => ['sometimes', 'nullable', 'string', 'size:2'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'vlr_id' => ['sometimes', 'nullable', 'integer', 'max:99999999'],
            'liquipedia_link' => ['sometimes', 'nullable', 'url', 'max:255'],
            'socials' => ['sometimes', 'array'],
            'socials.*' => ['nullable', 'string', 'max:255'],
        ]);

        $team->update($validated);

        return response()->json([
            'success' => true,
            'team' => $team->fresh(),
        ]);
    }
}
