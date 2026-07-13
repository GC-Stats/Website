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

use App\Http\Controllers\Controller;
use App\Models\Logo;
use App\Models\Player;
use App\Services\LogoUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiPlayerLogoController extends Controller
{
    public function __construct(private readonly LogoUploadService $logoUploadService) {}

    public function index(int $id): JsonResponse
    {
        $player = Player::findOrFail($id);

        $logos = $player->logos()->orderByDesc('from')->get()->map(fn ($logo) => [
            'id' => $logo->id,
            'from' => $logo->from,
            'until' => $logo->until,
            'url' => $this->logoUploadService->thumbnailUrl('players', $logo->id),
        ]);

        return response()->json(['logos' => $logos]);
    }

    public function upload(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'image' => ['required', 'file', 'image', 'max:10240'],
            'accept' => ['nullable', 'boolean'],
            'from' => ['nullable', 'date'],
            'until' => ['nullable', 'date'],
        ]);

        $uuid = $this->logoUploadService->storeLogoPair($request->file('image'), 'players');

        if ($request->boolean('accept')) {
            $player = Player::findOrFail($id ?? abort(422, 'player_id is required to accept instantly'));

            $logo = $this->acceptLogo($player, $uuid, $validated['from'] ?? null, $validated['until'] ?? null);

            return response()->json(['success' => true, 'uuid' => $uuid, 'logo' => $logo]);
        }

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
            abort(404, 'Logo not found');
        }

        $player = Player::findOrFail($validated['player_id']);

        $logo = $this->acceptLogo($player, $validated['uuid'], $validated['from'] ?? null, $validated['until'] ?? null);

        return response()->json(['success' => true, 'logo' => $logo]);
    }

    private function acceptLogo(Player $player, string $uuid, ?string $from = null, ?string $until = null): Logo
    {
        return $this->logoUploadService->acceptWithHistory($player, 'player', $uuid, $from, $until);
    }

    public function delete(string $uuid): JsonResponse
    {
        $this->logoUploadService->deleteLogo('player', 'players', $uuid);

        return response()->json(['success' => true]);
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
