<?php

/**
 * GC-Stats — News articles internal API controller
 *
 * CRUD for news articles. Author-scoped keys (X-Api-Key with news_author_id)
 * are restricted to their own articles. Admin keys have full access.
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
use App\Services\BunnyCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ApiNewsController extends Controller
{
    use ResolvesNewsAuthorScope;

    public function index(Request $request): JsonResponse
    {
        $scope = $this->authorScope($request);

        $query = News::with(['author', 'publisher', 'players', 'teams', 'tournaments'])
            ->when($scope !== null, fn ($q) => $q->where('author_id', $scope))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('lang'), fn ($q) => $q->where('lang', $request->lang))
            ->when($request->boolean('featured'), fn ($q) => $q->where('is_featured', true))
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        $perPage = min((int) $request->input('per_page', 20), 100);

        return response()->json($query->paginate($perPage));
    }

    public function show(int $id, Request $request): JsonResponse
    {
        $scope = $this->authorScope($request);

        $news = News::with(['author', 'publisher', 'players', 'teams', 'tournaments'])
            ->findOrFail($id);

        $this->assertAuthorAccess($scope, $news->author_id);

        return response()->json(['news' => $news]);
    }

    public function store(Request $request): JsonResponse
    {
        $scope = $this->authorScope($request);

        $validated = $request->validate([
            'author_id' => ['required', 'integer', 'exists:news_authors,id'],
            'publisher_id' => ['sometimes', 'nullable', 'integer', 'exists:news_publishers,id'],
            'lang' => ['required', 'string', 'max:5'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', 'unique:news,slug'],
            'excerpt' => ['sometimes', 'nullable', 'string'],
            'content' => ['required', 'string'],
            'image_cover' => ['sometimes', 'nullable', 'string', 'max:500'],
            'status' => ['sometimes', Rule::in(['draft', 'published', 'archived'])],
            'is_featured' => ['sometimes', 'boolean'],
            'show_on_home' => ['sometimes', 'boolean'],
            'published_at' => ['sometimes', 'nullable', 'date'],
        ]);

        // Author-scoped key can only create articles for themselves.
        if ($scope !== null && (int) $validated['author_id'] !== $scope) {
            abort(403, 'Access denied.');
        }

        $validated['slug'] ??= Str::slug($validated['title']).'-'.Str::random(5);
        $validated['published_at'] ??= ($validated['status'] ?? 'draft') === 'published' ? now() : null;

        $news = News::create($validated);

        return response()->json(['news' => $news->load(['author', 'publisher'])], 201);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $scope = $this->authorScope($request);

        $news = News::findOrFail($id);

        $this->assertAuthorAccess($scope, $news->author_id);

        $validated = $request->validate([
            'publisher_id' => ['sometimes', 'nullable', 'integer', 'exists:news_publishers,id'],
            'lang' => ['sometimes', 'string', 'max:5'],
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('news', 'slug')->ignore($news->id)],
            'excerpt' => ['sometimes', 'nullable', 'string'],
            'content' => ['sometimes', 'string'],
            'image_cover' => ['sometimes', 'nullable', 'string', 'max:500'],
            'status' => ['sometimes', Rule::in(['draft', 'published', 'archived'])],
            'is_featured' => ['sometimes', 'boolean'],
            'show_on_home' => ['sometimes', 'boolean'],
            'published_at' => ['sometimes', 'nullable', 'date'],
            // Admin-only: reassign author
            'author_id' => ['sometimes', 'integer', 'exists:news_authors,id'],
        ]);

        if ($scope !== null) {
            unset($validated['author_id'], $validated['is_featured']);
        }

        if (isset($validated['status']) && $validated['status'] === 'published' && ! $news->published_at) {
            $validated['published_at'] ??= now();
        }

        $news->update($validated);

        return response()->json(['news' => $news->fresh(['author', 'publisher'])]);
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        $scope = $this->authorScope($request);

        $news = News::findOrFail($id);

        // Authors cannot delete — only admins can.
        if ($scope !== null) {
            abort(403, 'Only admin keys can delete articles.');
        }

        $news->delete();

        return response()->json(['success' => true]);
    }

    public function publish(int $id, Request $request): JsonResponse
    {
        $scope = $this->authorScope($request);

        $news = News::findOrFail($id);

        $this->assertAuthorAccess($scope, $news->author_id);

        $news->update([
            'status' => 'published',
            'published_at' => $news->published_at ?? now(),
        ]);

        return response()->json(['news' => $news->fresh(['author', 'publisher'])]);
    }

    public function unpublish(int $id, Request $request): JsonResponse
    {
        $scope = $this->authorScope($request);

        $news = News::findOrFail($id);

        $this->assertAuthorAccess($scope, $news->author_id);

        $news->update(['status' => 'draft']);

        return response()->json(['news' => $news->fresh(['author', 'publisher'])]);
    }

    public function syncRelations(int $id, Request $request): JsonResponse
    {
        $scope = $this->authorScope($request);

        $news = News::findOrFail($id);

        $this->assertAuthorAccess($scope, $news->author_id);

        $validated = $request->validate([
            'players' => ['sometimes', 'array'],
            'players.*' => ['integer', 'exists:players,id'],
            'teams' => ['sometimes', 'array'],
            'teams.*' => ['integer', 'exists:teams,id'],
            'tournaments' => ['sometimes', 'array'],
            'tournaments.*' => ['integer', 'exists:tournaments,id'],
        ]);

        $removedUrls = [];
        $baseUrl = rtrim((string) config('app.url'), '/');

        if (array_key_exists('players', $validated)) {
            $removedIds = $news->players()->pluck('players.id')->diff($validated['players']);
            $removedUrls = array_merge($removedUrls, $removedIds->map(fn ($id) => "{$baseUrl}/player/{$id}")->all());
            $news->players()->sync($validated['players']);
        }

        if (array_key_exists('teams', $validated)) {
            $removedIds = $news->teams()->pluck('teams.id')->diff($validated['teams']);
            $removedUrls = array_merge($removedUrls, $removedIds->map(fn ($id) => "{$baseUrl}/team/{$id}")->all());
            $news->teams()->sync($validated['teams']);
        }

        if (array_key_exists('tournaments', $validated)) {
            $removedIds = $news->tournaments()->pluck('tournaments.id')->diff($validated['tournaments']);
            $removedUrls = array_merge($removedUrls, $removedIds->map(fn ($id) => "{$baseUrl}/tournaments/{$id}")->all());
            $news->tournaments()->sync($validated['tournaments']);
        }

        app(BunnyCache::class)->purgeUrls($removedUrls);

        // Fires the NewsObserver, which purges the article's own page, the
        // home feed, and every entity still linked after the sync above.
        $news->touch();

        return response()->json([
            'news' => $news->load(['author', 'publisher', 'players', 'teams', 'tournaments']),
        ]);
    }
}
