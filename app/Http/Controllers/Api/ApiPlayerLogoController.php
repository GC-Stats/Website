<?php

/**
 * GC-Stats — Player logo upload API controller
 *
 * Handles uploading, resizing and converting player profile photos to WebP,
 * storing them and recording their validity period (from/until) in the
 * player's logo history.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Public\Controller;
use App\Models\Logo;
use App\Models\Player;
use App\Services\LogoUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiPlayerLogoController extends Controller
{
    public function __construct(private readonly LogoUploadService $logoUploadService) {}

    public function upload(Request $request, int $id): JsonResponse
    {
        Player::findOrFail($id);

        $validated = $request->validate([
            'image' => ['required', 'file', 'image', 'max:10240'],
            'accept' => ['nullable', 'boolean'],
            'from' => ['nullable', 'date'],
            'until' => ['nullable', 'date'],
        ], [
            'image.max' => __('admin.logos.errors.image_too_large'),
        ]);

        $uuid = $this->logoUploadService->storeLogoPair($request->file('image'), 'players');

        return response()->json(['uuid' => $uuid]);
    }

    public function accept(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'player_id' => ['required', 'integer', 'exists:players,id'],
            'uuid' => ['required', 'uuid'],
            'from' => ['nullable', 'date'],
            'until' => ['nullable', 'date'],
        ]);

        if (! $this->logoUploadService->thumbnailExists('players', $validated['uuid'])) {
            abort(404, __('admin.logos.errors.not_found'));
        }

        $player = Player::findOrFail($validated['player_id']);

        $logo = $this->acceptLogo($player, $validated['uuid'], $validated['from'] ?? null, $validated['until'] ?? null);

        return response()->json(['success' => true, 'logo' => $logo]);
    }

    private function acceptLogo(Player $player, string $uuid, ?string $from = null, ?string $until = null): Logo
    {
        return $this->logoUploadService->acceptWithHistory($player, 'player', $uuid, $from, $until);
    }

    public function refuse(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'uuid' => ['required', 'uuid'],
        ]);

        $this->logoUploadService->deleteFiles('players', $validated['uuid']);

        return response()->json(['success' => true]);
    }
}
