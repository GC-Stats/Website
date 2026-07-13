<?php

/**
 * GC-Stats — Tournament logo upload API controller
 *
 * Handles uploading, resizing and converting tournament logos to WebP,
 * storing them and recording their validity period (from/until) in the
 * tournament's logo history.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Logo;
use App\Models\Tournament;
use App\Services\LogoUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiTournamentLogoController extends Controller
{
    public function __construct(private readonly LogoUploadService $logoUploadService) {}

    public function index(int $id): JsonResponse
    {
        $tournament = Tournament::findOrFail($id);

        $logos = $tournament->logos()->orderByDesc('from')->get()->map(fn ($logo) => [
            'id' => $logo->id,
            'from' => $logo->from,
            'until' => $logo->until,
            'url' => $this->logoUploadService->thumbnailUrl('tournaments', $logo->id),
        ]);

        return response()->json(['logos' => $logos]);
    }

    public function upload(Request $request, ?int $id = null): JsonResponse
    {
        $validated = $request->validate([
            'image' => ['required', 'file', 'image', 'max:10240'],
            'tournament_id' => ['nullable', 'integer', 'exists:tournaments,id'],
            'accept' => ['nullable', 'boolean'],
            'from' => ['nullable', 'date'],
            'until' => ['nullable', 'date'],
        ]);

        $uuid = $this->logoUploadService->storeLogoPair($request->file('image'), 'tournaments');

        if ($request->boolean('accept')) {
            $tournament = Tournament::findOrFail($id ?? $validated['tournament_id'] ?? abort(422, 'tournament_id is required to accept instantly'));

            $logo = $this->acceptLogo($tournament, $uuid, $validated['from'] ?? null, $validated['until'] ?? null);

            return response()->json(['success' => true, 'uuid' => $uuid, 'logo' => $logo]);
        }

        return response()->json(['uuid' => $uuid]);
    }

    public function accept(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tournament_id' => ['required', 'integer', 'exists:tournaments,id'],
            'uuid' => ['required', 'uuid'],
            'from' => ['nullable', 'date'],
            'until' => ['nullable', 'date'],
        ]);

        if (! $this->logoUploadService->thumbnailExists('tournaments', $validated['uuid'])) {
            abort(404, 'Logo not found');
        }

        $tournament = Tournament::findOrFail($validated['tournament_id']);

        $logo = $this->acceptLogo($tournament, $validated['uuid'], $validated['from'] ?? null, $validated['until'] ?? null);

        return response()->json(['success' => true, 'logo' => $logo]);
    }

    private function acceptLogo(Tournament $tournament, string $uuid, ?string $from = null, ?string $until = null): Logo
    {
        return $this->logoUploadService->acceptWithHistory($tournament, 'tournament', $uuid, $from, $until);
    }

    public function delete(string $uuid): JsonResponse
    {
        $this->logoUploadService->deleteLogo('tournament', 'tournaments', $uuid);

        return response()->json(['success' => true]);
    }

    public function refuse(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'uuid' => ['required', 'uuid'],
        ]);

        $this->logoUploadService->deleteFiles('tournaments', $validated['uuid']);

        return response()->json(['success' => true]);
    }
}
