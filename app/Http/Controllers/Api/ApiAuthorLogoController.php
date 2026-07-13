<?php

/**
 * GC-Stats — Author logo upload API controller
 *
 * Handles uploading, resizing and converting author profile photos to WebP,
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
use App\Models\NewsAuthor;
use App\Services\LogoUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiAuthorLogoController extends Controller
{
    public function __construct(private readonly LogoUploadService $logoUploadService) {}

    public function index(int $id): JsonResponse
    {
        $author = NewsAuthor::findOrFail($id);

        $logo = $author->currentLogo;

        return response()->json([
            'logo' => $logo ? [
                'id' => $logo->id,
                'url' => $this->logoUploadService->thumbnailUrl('authors', $logo->id),
            ] : null,
        ]);
    }

    public function upload(Request $request, $id): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'image', 'max:10240'],
            'author_id' => ['nullable', 'integer', 'exists:news_authors,id'],
        ]);

        $uuid = $this->logoUploadService->storeLogoPair($request->file('image'), 'authors');

        $author = NewsAuthor::findOrFail($id ?? abort(422, 'author_id is required'));

        $logo = $this->acceptLogo($author, $uuid);

        return response()->json(['success' => true, 'uuid' => $uuid, 'logo' => $logo]);
    }

    public function accept(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'author_id' => ['required', 'integer', 'exists:news_authors,id'],
            'uuid' => ['required', 'uuid'],
        ]);

        if (! $this->logoUploadService->thumbnailExists('authors', $validated['uuid'])) {
            abort(404, 'Logo not found');
        }

        $author = NewsAuthor::findOrFail($validated['author_id']);

        $logo = $this->acceptLogo($author, $validated['uuid']);

        return response()->json(['success' => true, 'logo' => $logo]);
    }

    private function acceptLogo(NewsAuthor $author, string $uuid): Logo
    {
        return $this->logoUploadService->acceptReplacing($author, 'author', $uuid, 'authors');
    }

    public function delete(string $uuid): JsonResponse
    {
        $this->logoUploadService->deleteLogo('author', 'authors', $uuid);

        return response()->json(['success' => true]);
    }

    public function refuse(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'uuid' => ['required', 'uuid'],
        ]);

        $this->logoUploadService->deleteFiles('authors', $validated['uuid']);

        return response()->json(['success' => true]);
    }
}
