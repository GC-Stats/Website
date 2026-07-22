<?php

/**
 * GC-Stats — Admin: news articles
 *
 * CRUD for news articles, plus lightweight status toggles (publish, archive,
 * feature, show-on-home) and the players/teams/tournaments relations picker
 * backed by the `news_relations` morph pivot. Reachable by site editors
 * (news.*) or by a publisher's own member with the matching
 * 'publisher.news.*' permission on the article's publisher (guard
 * 'publisher', see App\Support\PublisherPermissions) — see
 * App\Http\Controllers\Concerns\ManagesPublisherScopedNews. Having a
 * NewsAuthor profile grants no article capability by itself (see that
 * trait's docblock).
 *
 * The author is never picked from a list — creating an article always
 * bylines the current user's own NewsAuthor profile (requiring one to
 * exist), and it can't be reassigned afterward. A new article always starts
 * as a draft; there's no user-facing status picker — status only moves via
 * the dedicated publish()/archive() actions. archive() is gated by the
 * delete permission (news.delete / publisher.news.delete), same as
 * destroy() — archiving is the "soft" alternative to deleting.
 * is_featured/show_on_home stay site-editor-only (news.edit): whether an
 * article gets curated onto the homepage is an editorial site decision.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ManagesPublisherScopedNews;
use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\NewsAuthor;
use App\Models\NewsPublisher;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\HtmlSanitizer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class NewsController extends Controller
{
    use ManagesPublisherScopedNews;

    private const SORTABLE = ['title', 'author', 'publisher', 'status'];

    public function index(Request $request): View
    {
        $user = $request->user();
        $allowedPublisherIds = $user->can('news.view') ? null : $this->allowedPublisherIds($request, 'publisher.news.view');

        abort_if($allowedPublisherIds !== null && $allowedPublisherIds->isEmpty(), 403);

        $search = $request->get('q');
        $status = $request->get('status');
        $lang = $request->get('lang');
        $publisherId = $request->get('publisher_id');
        $authorId = $request->get('author_id');

        [$sort, $direction] = $this->resolveSort($request, self::SORTABLE, 'published_at', 'asc');

        $news = News::query()
            ->with(['author', 'publisher'])
            ->when($allowedPublisherIds !== null, fn ($query) => $query->whereIn('publisher_id', $allowedPublisherIds))
            ->when($search, fn ($query) => $query->where('title', 'like', '%'.$this->escapeLike($search).'%')
                ->orWhere('slug', 'like', '%'.$this->escapeLike($search).'%'))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($lang, fn ($query) => $query->where('lang', $lang))
            ->when($publisherId, fn ($query) => $query->where('publisher_id', $publisherId))
            ->when($authorId, fn ($query) => $query->where('author_id', $authorId))
            ->when($sort === 'title', fn ($query) => $query->orderBy('title', $direction))
            ->when($sort === 'author', fn ($query) => $query
                ->select('news.*')
                ->leftJoin('news_authors', 'news_authors.id', '=', 'news.author_id')
                ->orderBy('news_authors.name', $direction))
            ->when($sort === 'publisher', fn ($query) => $query
                ->select('news.*')
                ->leftJoin('news_publishers', 'news_publishers.id', '=', 'news.publisher_id')
                ->orderBy('news_publishers.name', $direction))
            ->when($sort === 'status', fn ($query) => $query->orderBy('status', $direction))
            ->when($sort === 'published_at', fn ($query) => $query->orderByDesc('published_at'))
            ->orderByDesc('news.id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.news.index', [
            'news' => $news,
            'search' => $search ?? '',
            'status' => $status ?? '',
            'lang' => $lang ?? '',
            'sort' => $sort ?? 'published_at',
            'direction' => $direction,
            'editablePublisherIds' => $user->can('news.edit') ? collect() : $this->allowedPublisherIds($request, 'publisher.news.edit'),
            'publishers' => NewsPublisher::orderBy('name')->get(['id', 'name']),
            'authors' => NewsAuthor::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $allowedPublisherIds = $request->user()->can('news.create') ? null : $this->allowedPublisherIds($request, 'publisher.news.edit');

        abort_if($allowedPublisherIds !== null && $allowedPublisherIds->isEmpty(), 403);

        if (! $request->user()->newsAuthor) {
            return redirect()->route('admin.news.authors.index')->with('status', 'author-profile-required');
        }

        return view('admin.news.create', $this->formData(null, $allowedPublisherIds));
    }

    public function searchRelations(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(['players', 'teams', 'tournaments'])],
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $term = '%'.$this->escapeLike($validated['q']).'%';

        $results = match ($validated['type']) {
            'players' => Player::where('handle', 'like', $term)->limit(10)->get(['id', 'handle as label']),
            'teams' => Team::where('name', 'like', $term)->limit(10)->get(['id', 'name as label']),
            'tournaments' => Tournament::where('name', 'like', $term)->limit(10)->get(['id', 'name as label']),
        };

        return response()->json($results);
    }

    public function store(Request $request): RedirectResponse
    {
        $allowedPublisherIds = $request->user()->can('news.create') ? null : $this->allowedPublisherIds($request, 'publisher.news.edit');

        abort_if($allowedPublisherIds !== null && $allowedPublisherIds->isEmpty(), 403);

        $author = $request->user()->newsAuthor;
        abort_unless($author, 403);

        $validated = $this->validated($request, null, $allowedPublisherIds);
        $validated['slug'] = ($validated['slug'] ?? null) ?: Str::slug($validated['title']);
        $validated['author_id'] = $author->id;
        $validated['status'] = 'draft';

        if ($allowedPublisherIds !== null) {
            unset($validated['is_featured'], $validated['show_on_home']);
        }

        $article = News::create($validated);

        $this->syncRelations($article, $request);

        return redirect()->route('admin.news.edit', $article)->with('status', 'article-created');
    }

    public function edit(Request $request, News $article): View
    {
        $this->ensureCanManageArticle($request, $article, 'news.edit', 'publisher.news.edit');

        $restricted = ! $request->user()->can('news.edit');

        return view('admin.news.edit', [
            ...$this->formData($article, $restricted ? $this->allowedPublisherIds($request, 'publisher.news.edit') : null),
            'canPublish' => $this->canManageArticle($request, $article, 'news.publish', 'publisher.news.publish'),
            'canArchive' => $this->canManageArticle($request, $article, 'news.delete', 'publisher.news.delete'),
        ]);
    }

    public function update(Request $request, News $article): RedirectResponse
    {
        $this->ensureCanManageArticle($request, $article, 'news.edit', 'publisher.news.edit');

        $restricted = ! $request->user()->can('news.edit');
        $allowedPublisherIds = $restricted ? $this->allowedPublisherIds($request, 'publisher.news.edit') : null;

        $validated = $this->validated($request, $article, $allowedPublisherIds);
        $validated['slug'] = ($validated['slug'] ?? null) ?: Str::slug($validated['title']);

        if ($restricted) {
            unset($validated['is_featured'], $validated['show_on_home']);
        }

        $article->update($validated);

        $this->syncRelations($article, $request);

        return back()->with('status', 'article-updated');
    }

    public function destroy(Request $request, News $article): RedirectResponse
    {
        $this->ensureCanManageArticle($request, $article, 'news.delete', 'publisher.news.delete');

        $article->delete();

        return redirect()->route('admin.news.index')->with('status', 'article-deleted');
    }

    public function publish(Request $request, News $article): RedirectResponse
    {
        $this->ensureCanManageArticle($request, $article, 'news.publish', 'publisher.news.publish');

        $article->update([
            'status' => 'published',
            'published_at' => $article->published_at ?? now(),
        ]);

        return back()->with('status', 'article-published');
    }

    /**
     * The "soft delete" for an article — same permission as destroy(),
     * since it's an alternative to deleting rather than a lighter action.
     */
    public function archive(Request $request, News $article): RedirectResponse
    {
        $this->ensureCanManageArticle($request, $article, 'news.delete', 'publisher.news.delete');

        $article->update(['status' => 'archived']);

        return back()->with('status', 'article-archived');
    }

    public function toggleFeature(News $article): RedirectResponse
    {
        $article->update(['is_featured' => ! $article->is_featured]);

        return back()->with('status', 'article-updated');
    }

    public function toggleShowOnHome(News $article): RedirectResponse
    {
        $article->update(['show_on_home' => ! $article->show_on_home]);

        return back()->with('status', 'article-updated');
    }

    /**
     * @param  Collection<int, int>|null  $allowedPublisherIds  when not null, 'publisher_id' must be one of these (or left empty for an author-only submission)
     */
    private function validated(Request $request, ?News $article, ?Collection $allowedPublisherIds): array
    {
        $validated = $request->validate([
            'publisher_id' => ['nullable', 'integer', 'exists:news_publishers,id'],
            'lang' => ['required', 'string', 'max:5'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('news', 'slug')->ignore($article?->id)],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'is_featured' => ['sometimes', 'boolean'],
            'show_on_home' => ['sometimes', 'boolean'],
        ]);

        // Rendered unescaped on the public article page — any author's raw
        // markup goes through the same allow-list, not just when a site
        // admin happens to be the one writing it. See App\Services\HtmlSanitizer.
        $validated['content'] = app(HtmlSanitizer::class)->sanitize($validated['content']);

        // image_cover is deliberately NOT accepted here — the only path to
        // set it is Admin\NewsMediaController::setCover(), which enforces
        // the image is one of *this article's* own uploaded NewsImage rows.
        // Accepting it as a free string here would let anyone who can edit
        // an article point its cover at an arbitrary external URL.

        $publisherId = $validated['publisher_id'] ?? null;
        $publisherUnchanged = $article && (int) $publisherId === (int) $article->publisher_id;

        if ($allowedPublisherIds !== null && $publisherId && ! $publisherUnchanged) {
            abort_unless($allowedPublisherIds->contains($publisherId), 403);
        }

        return $validated;
    }

    private function syncRelations(News $article, Request $request): void
    {
        $article->players()->sync($request->input('players', []));
        $article->teams()->sync($request->input('teams', []));
        $article->tournaments()->sync($request->input('tournaments', []));
    }

    /**
     * @param  Collection<int, int>|null  $restrictToPublisherIds  when not null, the publisher picker only lists these
     */
    private function formData(?News $article, ?Collection $restrictToPublisherIds): array
    {
        return [
            'article' => $article,
            'publishers' => NewsPublisher::query()
                ->when($restrictToPublisherIds !== null, fn ($query) => $query->whereIn('id', $restrictToPublisherIds))
                ->orderBy('name')->get(['id', 'name']),
            'selectedPlayers' => $article?->players()->get(['players.id', 'players.handle as label']) ?? collect(),
            'selectedTeams' => $article?->teams()->get(['teams.id', 'teams.name as label']) ?? collect(),
            'selectedTournaments' => $article?->tournaments()->get(['tournaments.id', 'tournaments.name as label']) ?? collect(),
            'images' => $article ? $article->images()->orderByDesc('created_at')->get() : collect(),
        ];
    }
}
