<?php

/**
 * GC-Stats — News publisher logo upload API controller
 *
 * Handles uploading, resizing and converting publisher logos to WebP,
 * storing them in the logos table. No history is kept — accepting a new logo
 * deletes the previous one from storage and the database.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Logo;
use App\Models\NewsPublisher;
use App\Services\LogoUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiPublisherLogoController extends Controller
{
    public function __construct(private readonly LogoUploadService $logoUploadService) {}

    public function index(int $id): JsonResponse
    {
        $publisher = NewsPublisher::findOrFail($id);

        $logo = $publisher->currentLogo;

        return response()->json([
            'logo' => $logo ? [
                'id' => $logo->id,
                'url' => $this->logoUploadService->thumbnailUrl('publishers', $logo->id),
            ] : null,
        ]);
    }

    public function upload(Request $request, $id): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'image', 'max:10240'],
        ]);

        $uuid = $this->logoUploadService->storeLogoPair($request->file('image'), 'publishers');

        $publisher = NewsPublisher::findOrFail($id ?? abort(422, 'publisher_id is required to accept instantly'));

        $logo = $this->acceptLogo($publisher, $uuid);

        return response()->json(['success' => true, 'uuid' => $uuid, 'logo' => $logo]);
    }

    public function accept(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'publisher_id' => ['required', 'integer', 'exists:news_publishers,id'],
            'uuid' => ['required', 'uuid'],
        ]);

        if (! $this->logoUploadService->thumbnailExists('publishers', $validated['uuid'])) {
            abort(404, 'Logo not found');
        }

        $publisher = NewsPublisher::findOrFail($validated['publisher_id']);

        $logo = $this->acceptLogo($publisher, $validated['uuid']);

        return response()->json(['success' => true, 'logo' => $logo]);
    }

    private function acceptLogo(NewsPublisher $publisher, string $uuid): Logo
    {
        return $this->logoUploadService->acceptReplacing($publisher, 'publisher', $uuid, 'publishers');
    }

    public function delete(string $uuid): JsonResponse
    {
        $this->logoUploadService->deleteLogo('publisher', 'publishers', $uuid);

        return response()->json(['success' => true]);
    }

    public function refuse(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'uuid' => ['required', 'uuid'],
        ]);

        $this->logoUploadService->deleteFiles('publishers', $validated['uuid']);

        return response()->json(['success' => true]);
    }
}
