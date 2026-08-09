<?php

/**
 * GC-Stats — Admin: news authors
 *
 * Site admins see and manage every author profile; an author linked to a
 * User account (news_authors.user_id) reaches the same `show`/`update`
 * actions for their own profile only — fully editable, since it's their
 * personal byline (name, slug, bio, socials, photo).
 *
 * The self-service subset (myProfile()/show()/update()/updateLogo() for
 * *your own* profile) is also reachable from an organization's dashboard —
 * see myProfile()'s docblock — since it's part of "the news block" a
 * dashboard news contributor needs, even though a NewsAuthor profile itself
 * is personal, not organization-scoped (see this class's own docblock and
 * App\Models\NewsAuthor). Managing *other* authors (the index() listing,
 * assigning a different user, deleting) stays admin-only.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesDashboardContext;
use App\Http\Controllers\Public\Controller;
use App\Models\NewsAuthor;
use App\Models\Organization;
use App\Models\User;
use App\Services\HtmlSanitizer;
use App\Services\LogoUploadService;
use App\Support\Activity\ActivityChangeSet;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class NewsAuthorController extends Controller
{
    use ResolvesDashboardContext;

    private function routePrefix(): string
    {
        return $this->dashboardContext('organization-dashboard.news.author.', 'personal-dashboard.news.author.', 'admin.news.authors.');
    }

    private function viewName(string $page): string
    {
        return $this->dashboardContext(
            "organization.dashboard.news.author.{$page}",
            "personal-dashboard.news.author.{$page}",
            "admin.news.authors.{$page}",
        );
    }

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

        [$sort, $direction] = $this->resolveSort($request, ['name', 'count'], 'name', 'asc');

        $authors = NewsAuthor::query()
            ->withCount('news')
            ->when($search, fn ($query) => $query->where('name', 'like', '%'.$this->escapeLike($search).'%'))
            ->when($sort === 'count', fn ($query) => $query->orderBy('news_count', $direction))
            ->when($sort === 'name', fn ($query) => $query->orderBy('name', $direction))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.news.authors.index', [
            'authors' => $authors,
            'search' => $search ?? '',
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    /**
     * The organization-dashboard entry point ("my author profile" in the
     * news block) — always self-service regardless of site-wide
     * permissions, unlike index()'s branch above which only falls back to
     * self-service for users without news.authors.view. $organization is
     * only ever bound here (never for the flat admin self-service path,
     * which still goes through index()) — used purely to pick the
     * dashboard route prefix/view wrapper, since the profile itself isn't
     * organization data.
     */
    public function myProfile(Request $request, ?Organization $organization = null): View|RedirectResponse
    {
        $ownProfile = $request->user()->newsAuthor;

        if ($ownProfile) {
            return redirect()->route($this->routePrefix().'show', $organization ? [$organization, $ownProfile] : $ownProfile);
        }

        return view($this->viewName('create-self'), [
            'organization' => $organization,
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    public function show(Request $request, ?Organization $organization = null, ?NewsAuthor $author = null): View
    {
        $author = $this->requiredAuthor($author);
        $this->ensureCanManage($request, $author);

        return view($this->viewName('show'), [
            'author' => $author,
            'organization' => $organization,
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    public function update(Request $request, ?Organization $organization = null, ?NewsAuthor $author = null): RedirectResponse
    {
        $author = $this->requiredAuthor($author);
        $this->ensureCanManage($request, $author);

        // Never true when editing your own profile — the linked-user field
        // is for a site editor reassigning *someone else's* profile, not
        // for relinking yourself, see resources/views/news/_author-show.blade.php.
        $canManageUser = $request->user()->can('news.authors.edit') && $author->user_id !== $request->user()->id;

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
            'username' => $canManageUser ? ['nullable', 'string', 'exists:users,username'] : ['prohibited'],
        ]);

        $userId = $author->user_id;

        if ($canManageUser) {
            $userId = null;

            if (filled($validated['username'] ?? null)) {
                $user = User::where('username', $validated['username'])->firstOrFail();

                if (NewsAuthor::where('user_id', $user->id)->where('id', '!=', $author->id)->exists()) {
                    throw ValidationException::withMessages(['username' => __('admin.news.authors.form.user_already_linked')]);
                }

                $userId = $user->id;
            }
        }

        $author->update([
            'name' => $validated['name'],
            'slug' => ($validated['slug'] ?? null) ?: Str::slug($validated['name']),
            'bio' => $validated['bio'] ?? null,
            'socials' => array_filter($validated['socials'] ?? [], fn ($value) => filled($value)),
            'user_id' => $userId,
        ]);

        activity('publisher')->performedOn($author)->causedBy($request->user())
            ->withProperties(ActivityChangeSet::fromModel($author, ['name', 'slug', 'bio', 'socials', 'user_id'])->toArray())
            ->log('author.updated');

        return back()->with('status', 'author-updated');
    }

    public function updateLogo(Request $request, ?Organization $organization = null, ?NewsAuthor $author = null): RedirectResponse
    {
        $author = $this->requiredAuthor($author);
        $this->ensureCanManage($request, $author);

        $logoUploadService = app(LogoUploadService::class);

        $validated = $request->validate(['logo' => ['required', 'file', 'image', 'max:10240']]);

        $oldLogoId = $author->logos->pluck('id')->first();

        $uuid = $logoUploadService->storeLogoPair($validated['logo'], 'authors');
        $logoUploadService->acceptReplacing($author, 'author', $uuid, 'authors');

        activity('publisher')->performedOn($author)->causedBy($request->user())
            ->withProperties(['changes' => ['logo_id' => ['old' => $oldLogoId, 'new' => $uuid]]])
            ->log('author.logo_updated');

        return back()->with('status', 'logo-updated');
    }

    /**
     * A site editor with news.authors.edit can create a profile for anyone
     * (optionally linked to a user). Otherwise this is self-service: any
     * admin-panel user without a profile of their own yet may create
     * exactly one, always linked to themselves.
     */
    public function store(Request $request, ?Organization $organization = null): RedirectResponse
    {
        $isAdmin = $request->user()->can('news.authors.edit');

        abort_unless($isAdmin || ! $request->user()->newsAuthor, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100', 'unique:news_authors,slug'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'username' => $isAdmin ? ['nullable', 'string', 'exists:users,username'] : ['prohibited'],
        ]);

        $validated['slug'] = ($validated['slug'] ?? null) ?: Str::slug($validated['name']);

        if ($isAdmin) {
            $userId = null;

            if (filled($validated['username'] ?? null)) {
                $user = User::where('username', $validated['username'])->firstOrFail();

                if (NewsAuthor::where('user_id', $user->id)->exists()) {
                    throw ValidationException::withMessages(['username' => __('admin.news.authors.form.user_already_linked')]);
                }

                $userId = $user->id;
            }

            $validated['user_id'] = $userId;
        } else {
            $validated['user_id'] = $request->user()->id;
        }

        unset($validated['username']);

        $author = NewsAuthor::create($validated);

        activity('publisher')->performedOn($author)->causedBy($request->user())
            ->withProperties(ActivityChangeSet::fromCreated($author, ['name', 'slug', 'bio', 'user_id'])->toArray())
            ->log('author.created');

        return redirect()->route($this->routePrefix().'show', $organization ? [$organization, $author] : $author)
            ->with('status', 'author-created');
    }

    public function destroy(Request $request, NewsAuthor $author): RedirectResponse
    {
        $name = $author->name;
        $author->delete();

        activity('publisher')->causedBy($request->user())
            ->withProperties(['name' => $name])
            ->log('author.deleted');

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

    /**
     * $author is only nullable in show()/update()/updateLogo() so its
     * position can match $organization's — see
     * Admin\NewsController::requiredArticle()'s docblock for why (same
     * Laravel implicit-binding-is-positional-not-named quirk applies here).
     */
    private function requiredAuthor(?NewsAuthor $author): NewsAuthor
    {
        abort_unless($author, 404);

        return $author;
    }
}
