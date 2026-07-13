<?php

/**
 * GC-Stats — News publishers internal API controller
 *
 * CRUD for publisher organisations. Author-scoped keys can list and read
 * publishers linked to their articles, but cannot create or delete them.
 * Admins have full access.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesNewsAuthorScope;
use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\NewsPublisher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiNewsPublisherController extends Controller
{
    use ResolvesNewsAuthorScope;

    public function index(Request $request): JsonResponse
    {
        $scope = $this->authorScope($request);

        if ($scope !== null) {
            $publisherIds = News::where('author_id', $scope)
                ->whereNotNull('publisher_id')
                ->distinct()
                ->pluck('publisher_id');

            $publishers = NewsPublisher::with('currentLogo')->whereIn('id', $publisherIds)->orderBy('name')->get();
        } else {
            $publishers = NewsPublisher::with('currentLogo')->withCount('news')->orderBy('name')->get();
        }

        return response()->json(['publishers' => $publishers]);
    }

    public function show(int $id, Request $request): JsonResponse
    {
        $scope = $this->authorScope($request);

        if ($scope !== null) {
            $usedByAuthor = News::where('author_id', $scope)
                ->where('publisher_id', $id)
                ->exists();

            if (! $usedByAuthor) {
                abort(403, 'Access denied.');
            }
        }

        $publisher = NewsPublisher::with('currentLogo')->withCount('news')->findOrFail($id);

        return response()->json(['publisher' => $publisher]);
    }

    public function store(Request $request): JsonResponse
    {
        if ($this->authorScope($request) !== null) {
            abort(403, 'Only admin keys can create publishers.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:100', 'unique:news_publishers,slug'],
            'socials' => ['sometimes', 'array'],
        ]);

        $validated['slug'] ??= Str::slug($validated['name']);

        $publisher = NewsPublisher::create($validated);

        return response()->json(['publisher' => $publisher->load('currentLogo')], 201);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        if ($this->authorScope($request) !== null) {
            abort(403, 'Only admin keys can edit publishers.');
        }

        $publisher = NewsPublisher::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:100', "unique:news_publishers,slug,{$id}"],
            'socials' => ['sometimes', 'array'],
        ]);

        $publisher->update($validated);

        return response()->json(['publisher' => $publisher->fresh('currentLogo')]);
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        if ($this->authorScope($request) !== null) {
            abort(403, 'Only admin keys can delete publishers.');
        }

        NewsPublisher::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }
}
