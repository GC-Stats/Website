<?php

/**
 * GC-Stats — Search controller
 *
 * Handles the dedicated search results page: all matching players, teams
 * and tournaments for a query, with type filtering and sorting.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers;

use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class SearchController extends Controller
{
    public function index(Request $request, SearchService $searchService)
    {
        $term = strtolower(trim((string) $request->query('q', '')));
        $type = $request->query('type', 'all');
        $sort = in_array($request->query('sort'), ['relevance', 'name', 'popularity']) ? $request->query('sort') : 'relevance';

        $results = [];

        if (mb_strlen($term) >= 2) {
            $results = Cache::remember("search_full_v2_{$term}", now()->addMinutes(15), fn () => $searchService->search($term, perTypeLimit: 50, candidateLimit: 100));
        }

        $items = collect($results)
            ->flatMap(fn ($group, $groupType) => collect($group)->map(fn ($item) => array_merge($item, ['type' => $groupType])))
            ->when($type !== 'all', fn ($collection) => $collection->where('type', $type));

        $items = match ($sort) {
            'name' => $items->sortBy(fn ($item) => strtolower($item['handle'] ?? $item['name']))->values(),
            'popularity' => $items->sortByDesc('popularity')->values(),
            default => $items->sortByDesc('score')->values(),
        };

        $perPage = 20;
        $page = $request->query('page', 1);
        $paginated = new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('search.index', [
            'query' => $term,
            'type' => $type,
            'sort' => $sort,
            'results' => $paginated,
            'totalCount' => $items->count(),
        ]);
    }
}
