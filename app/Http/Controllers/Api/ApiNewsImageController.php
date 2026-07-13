<?php

/**
 * GC-Stats — News image upload API controller
 *
 * Handles uploading, processing and storing news cover images as WebP.
 * Each image is recorded in the DB with an optional link to a news article
 * and the author who uploaded it.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesNewsAuthorScope;
use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\NewsImage;
use App\Services\LogoUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiNewsImageController extends Controller
{
    use ResolvesNewsAuthorScope;

    public function __construct(private readonly LogoUploadService $logoUploadService) {}

    public function index(int $newsId): JsonResponse
    {
        $news = News::findOrFail($newsId);

        $images = $news->images()->with('author')->orderByDesc('created_at')->get();

        return response()->json(['images' => $images]);
    }

    public function upload(Request $request): JsonResponse
    {
        $scope = $this->authorScope($request);

        $validated = $request->validate([
            'image' => ['required', 'file', 'image', 'max:10240'],
            'news_id' => ['sometimes', 'nullable', 'integer', 'exists:news,id'],
            'author_id' => ['sometimes', 'nullable', 'integer', 'exists:news_authors,id'],
        ]);

        if ($scope !== null && isset($validated['author_id']) && (int) $validated['author_id'] !== $scope) {
            abort(403, 'Access denied.');
        }

        $authorId = $validated['author_id'] ?? ($scope ?? null);

        $uuid = (string) Str::uuid();

        $this->logoUploadService->storeImage($request->file('image'), "news/{$uuid}/cover.webp", 1400, null, 85);

        $record = NewsImage::create([
            'id' => $uuid,
            'news_id' => $validated['news_id'] ?? null,
            'author_id' => $authorId,
        ]);

        return response()->json(['image' => $record->load('author')], 201);
    }

    public function link(Request $request): JsonResponse
    {
        $scope = $this->authorScope($request);

        $validated = $request->validate([
            'uuid' => ['required', 'uuid', 'exists:news_images,id'],
            'news_id' => ['required', 'integer', 'exists:news,id'],
        ]);

        $image = NewsImage::findOrFail($validated['uuid']);

        if ($scope !== null && $image->author_id !== $scope) {
            abort(403, 'Access denied.');
        }

        $image->update(['news_id' => $validated['news_id']]);

        return response()->json(['image' => $image->fresh('author')]);
    }

    public function delete(Request $request, string $uuid): JsonResponse
    {
        $scope = $this->authorScope($request);

        $image = NewsImage::findOrFail($uuid);

        if ($scope !== null && $image->author_id !== $scope) {
            abort(403, 'Access denied.');
        }

        $this->logoUploadService->deleteFiles('news', $image->id);

        $image->delete();

        return response()->json(['success' => true]);
    }
}
