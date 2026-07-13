<?php

/**
 * GC-Stats — News authors internal API controller
 *
 * Admin-only CRUD for news author profiles. Author-scoped keys can only
 * read and update their own profile; they cannot create or delete authors.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesNewsAuthorScope;
use App\Http\Controllers\Controller;
use App\Models\NewsAuthor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiNewsAuthorController extends Controller
{
    use ResolvesNewsAuthorScope;

    public function index(Request $request): JsonResponse
    {
        $scope = $this->authorScope($request);

        $authors = $scope !== null
            ? NewsAuthor::with('currentLogo')->where('id', $scope)->get()
            : NewsAuthor::with('currentLogo')->orderBy('name')->get();

        return response()->json(['authors' => $authors]);
    }

    public function show(int $id, Request $request): JsonResponse
    {
        $scope = $this->authorScope($request);

        if ($scope !== null && $scope !== $id) {
            abort(403, 'Access denied.');
        }

        $author = NewsAuthor::with('currentLogo')->withCount('news')->findOrFail($id);

        return response()->json(['author' => $author]);
    }

    public function store(Request $request): JsonResponse
    {
        if ($this->authorScope($request) !== null) {
            abort(403, 'Only admin keys can create authors.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:100', 'unique:news_authors,slug'],
            'bio' => ['sometimes', 'nullable', 'string'],
            'socials' => ['sometimes', 'array'],
        ]);

        $validated['slug'] ??= Str::slug($validated['name']);

        $author = NewsAuthor::create($validated);

        return response()->json(['author' => $author->load('currentLogo')], 201);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $scope = $this->authorScope($request);

        if ($scope !== null && $scope !== $id) {
            abort(403, 'Access denied.');
        }

        $author = NewsAuthor::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:100', "unique:news_authors,slug,{$id}"],
            'bio' => ['sometimes', 'nullable', 'string'],
            'socials' => ['sometimes', 'array'],
        ]);

        $author->update($validated);

        return response()->json(['author' => $author->fresh('currentLogo')]);
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        if ($this->authorScope($request) !== null) {
            abort(403, 'Only admin keys can delete authors.');
        }

        NewsAuthor::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }
}
