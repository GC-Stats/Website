<?php

/**
 * GC-Stats — Team logo upload API controller
 *
 * Handles uploading, resizing and converting team logos to WebP, storing
 * them and recording their validity period (from/until) in the team's
 * logo history.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Public\Controller;
use App\Models\Logo;
use App\Models\Team;
use App\Services\LogoUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiTeamLogoController extends Controller
{
    public function __construct(private readonly LogoUploadService $logoUploadService) {}

    public function upload(Request $request, int $id): JsonResponse
    {
        Team::findOrFail($id ?? abort(422, 'team_id is required to accept instantly'));

        $validated = $request->validate([
            'image' => ['required', 'file', 'image', 'max:10240'],
            'accept' => ['nullable', 'boolean'],
            'from' => ['nullable', 'date'],
            'until' => ['nullable', 'date'],
        ]);

        $uuid = $this->logoUploadService->storeLogoPair($request->file('image'), 'teams');

        return response()->json(['uuid' => $uuid]);
    }

    public function accept(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'team_id' => ['required', 'integer', 'exists:teams,id'],
            'uuid' => ['required', 'uuid'],
            'from' => ['nullable', 'date'],
            'until' => ['nullable', 'date'],
        ]);

        if (! $this->logoUploadService->thumbnailExists('teams', $validated['uuid'])) {
            abort(404, 'Logo not found');
        }

        $team = Team::findOrFail($validated['team_id']);

        $logo = $this->acceptLogo($team, $validated['uuid'], $validated['from'] ?? null, $validated['until'] ?? null);

        return response()->json(['success' => true, 'logo' => $logo]);
    }

    private function acceptLogo(Team $team, string $uuid, ?string $from = null, ?string $until = null): Logo
    {
        return $this->logoUploadService->acceptWithHistory($team, 'team', $uuid, $from, $until);
    }

    public function refuse(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'uuid' => ['required', 'uuid'],
        ]);

        $this->logoUploadService->deleteFiles('teams', $validated['uuid']);

        return response()->json(['success' => true]);
    }
}
