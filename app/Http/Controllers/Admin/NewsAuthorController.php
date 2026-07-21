<?php

/**
 * GC-Stats — Admin: news authors
 *
 * Site admins see and manage every author profile; an author linked to a
 * User account (news_authors.user_id) reaches the same `show`/`update`
 * actions for their own profile only — fully editable, since it's their
 * personal byline (name, slug, bio, socials, photo).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsAuthor;
use App\Services\HtmlSanitizer;
use App\Services\LogoUploadService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class NewsAuthorController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if (! $request->user()->can('news.authors.view')) {
            $ownProfile = $request->user()->newsAuthor;

            if ($ownProfile) {
                return redirect()->route('admin.news.authors.show', $ownProfile);
            }

            return view('admin.news.authors.create-self');
        }

        $search = $request->get('q');

        $authors = NewsAuthor::query()
            ->withCount('news')
            ->when($search, fn ($query) => $query->where('name', 'like', '%'.$this->escapeLike($search).'%'))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.news.authors.index', [
            'authors' => $authors,
            'search' => $search ?? '',
        ]);
    }

    public function show(Request $request, NewsAuthor $author): View
    {
        $this->ensureCanManage($request, $author);

        return view('admin.news.authors.show', [
            'author' => $author,
        ]);
    }

    public function update(Request $request, NewsAuthor $author): RedirectResponse
    {
        $this->ensureCanManage($request, $author);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100', Rule::unique('news_authors', 'slug')->ignore($author->id)],
            'bio' => ['nullable', 'string', 'max:2000'],
            'socials' => ['nullable', 'array'],
            'socials.*' => ['nullable', 'string', 'max:255', function ($attribute, $value, $fail) {
                if (! HtmlSanitizer::isSafeUrl($value)) {
                    $fail('The '.$attribute.' field must be a valid link.');
                }
            }],
            'user_id' => ['nullable', 'integer', 'exists:users,id', Rule::unique('news_authors', 'user_id')->ignore($author->id)],
        ]);

        $author->update([
            'name' => $validated['name'],
            'slug' => ($validated['slug'] ?? null) ?: Str::slug($validated['name']),
            'bio' => $validated['bio'] ?? null,
            'socials' => array_filter($validated['socials'] ?? [], fn ($value) => filled($value)),
            'user_id' => $request->user()->can('news.authors.edit') ? ($validated['user_id'] ?? null) : $author->user_id,
        ]);

        return back()->with('status', 'author-updated');
    }

    public function updateLogo(Request $request, NewsAuthor $author, LogoUploadService $logoUploadService): RedirectResponse
    {
        $this->ensureCanManage($request, $author);

        $validated = $request->validate(['logo' => ['required', 'file', 'image', 'max:10240']]);

        $uuid = $logoUploadService->storeLogoPair($validated['logo'], 'authors');
        $logoUploadService->acceptReplacing($author, 'author', $uuid, 'authors');

        return back()->with('status', 'logo-updated');
    }

    /**
     * A site editor with news.authors.edit can create a profile for anyone
     * (optionally linked to a user). Otherwise this is self-service: any
     * admin-panel user without a profile of their own yet may create
     * exactly one, always linked to themselves.
     */
    public function store(Request $request): RedirectResponse
    {
        $isAdmin = $request->user()->can('news.authors.edit');

        abort_unless($isAdmin || ! $request->user()->newsAuthor, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100', 'unique:news_authors,slug'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'user_id' => $isAdmin ? ['nullable', 'integer', 'exists:users,id', 'unique:news_authors,user_id'] : ['prohibited'],
        ]);

        $validated['slug'] = ($validated['slug'] ?? null) ?: Str::slug($validated['name']);
        $validated['user_id'] = $isAdmin ? ($validated['user_id'] ?? null) : $request->user()->id;

        $author = NewsAuthor::create($validated);

        return redirect()->route('admin.news.authors.show', $author)->with('status', 'author-created');
    }

    public function destroy(NewsAuthor $author): RedirectResponse
    {
        $author->delete();

        return redirect()->route('admin.news.authors.index')->with('status', 'author-deleted');
    }

    /**
     * A site editor with news.authors.edit can manage any author; otherwise
     * only the User linked via news_authors.user_id may touch their own
     * profile — this is the "100% editable, but only your own" rule.
     */
    private function ensureCanManage(Request $request, NewsAuthor $author): void
    {
        $user = $request->user();

        abort_unless($user->can('news.authors.edit') || $author->user_id === $user->id, 403);
    }
}
