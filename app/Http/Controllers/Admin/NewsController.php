<?php

/**
 * GC-Stats — Admin: news articles
 *
 * CRUD for news articles, plus lightweight status toggles (publish, archive,
 * feature, show-on-home), the review discussion (news_comments) and the
 * players/teams/tournaments relations picker backed by the `news_relations`
 * morph pivot. Reachable by site editors (news.*), by an organization's own
 * member with the matching 'organization.news.*' permission, or by a lone
 * author holding the site-wide 'news.author' permission acting on their own
 * org-less articles.
 *
 * The author is never picked from a list — creating an article always
 * bylines the current user's own NewsAuthor profile (requiring one to
 * exist), and it can't be reassigned afterward. A new article always starts
 * as a draft; there's no user-facing status picker — status only moves via
 * the dedicated publish()/archive() actions. archive() is gated by the
 * delete permission (news.delete / organization.news.delete), same as
 * destroy() — archiving is the "soft" alternative to deleting.
 * is_featured/show_on_home stay site-editor-only (news.edit): whether an
 * article gets curated onto the homepage is an editorial site decision.
 *
 * The review workflow (markValidated()/comment actions) only applies to
 * organization-attributed articles: "validated" is a precondition checked
 * at publish time (publish() requires the extra news.publish.unvalidated /
 * organization.news.publish.unvalidated permission to publish an
 * unvalidated article), not a status value itself — and any further edit
 * after validation resets it, since the reviewed content no longer matches
 * what was approved.
 *
 * Dual-context, like Organization\RoleController: this same controller
 * backs both admin.news.* (flat, cross-organization, extends admin.layout)
 * and organization-dashboard.news.* (scoped to the {organization} bound in
 * the dashboard URL, extends organization.layout) — see
 * isDashboard()/routePrefix()/viewName() and the shared content partials in
 * resources/views/news/. When $organization is bound (dashboard routes
 * only — admin routes carry no {organization} segment, so it stays null
 * there), every query and permission check is scoped to that one
 * organization instead of "every organization this user can touch".
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ManagesNewsAccess;
use App\Http\Controllers\Concerns\ResolvesDashboardContext;
use App\Http\Controllers\Public\Controller;
use App\Models\News;
use App\Models\NewsComment;
use App\Models\Organization;
use App\Models\Player;
use App\Models\Staff;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\HtmlSanitizer;
use App\Services\OrganizationAccessService;
use App\Support\Activity\ActivityChangeSet;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class NewsController extends Controller
{
    use ManagesNewsAccess;
    use ResolvesDashboardContext;

    private const SORTABLE = ['title', 'author', 'organization', 'status'];

    public const REVIEWABLE_FIELDS = ['title', 'excerpt', 'content', 'image_cover'];

    private function routePrefix(): string
    {
        return $this->dashboardContext('organization-dashboard.news.', 'personal-dashboard.news.', 'admin.news.');
    }

    private function viewName(string $page): string
    {
        return $this->dashboardContext(
            "organization.dashboard.news.{$page}",
            "personal-dashboard.news.{$page}",
            "admin.news.{$page}",
        );
    }

    public function index(Request $request, ?Organization $organization = null): View
    {
        $user = $request->user();
        $access = app(OrganizationAccessService::class);

        $ownAuthorId = null;

        if ($organization) {
            abort_unless($access->hasOrganizationPermission($user, $organization, 'news.view', 'organization.news.view'), 403);
            $editableOrganizationIds = null;
            $canEditArticle = fn () => $access->hasOrganizationPermission($user, $organization, 'news.edit', 'organization.news.edit');
        } elseif ($this->isPersonalDashboard()) {
            // No abort here: reaching this route at all already requires
            // news.author (see routes/personal-dashboard.php), and the
            // listing is always scoped to the user's own author id below —
            // there's nothing left to further restrict.
            $ownAuthorId = $user->newsAuthor?->id;
            $editableOrganizationIds = collect();
            $canEditArticle = fn () => true;
        } else {
            $allowedOrganizationIds = $user->can('news.view') ? null : $this->allowedOrganizationIds($request, 'organization.news.view');
            abort_if($allowedOrganizationIds !== null && $allowedOrganizationIds->isEmpty(), 403);

            $editableOrganizationIds = $user->can('news.edit') ? collect() : $this->allowedOrganizationIds($request, 'organization.news.edit');
            $canEditArticle = fn (News $article) => $user->can('news.edit') || $editableOrganizationIds->contains($article->organization_id);
        }

        $search = $request->get('q');
        $status = $request->get('status');
        $lang = $request->get('lang');
        $authorId = $request->get('author_id');

        [$sort, $direction] = $this->resolveSort($request, self::SORTABLE, 'published_at', 'asc');

        $news = News::query()
            ->with(['author', 'organization'])
            ->when($organization, fn ($query) => $query->where('organization_id', $organization->id))
            ->when($this->isPersonalDashboard(), fn ($query) => $ownAuthorId
                ? $query->where('author_id', $ownAuthorId)
                // No profile yet (see the isPersonalDashboard() branch above) —
                // an Eloquent where() against a null value turns into
                // whereNull(), which would otherwise return every
                // unattributed article site-wide instead of none.
                : $query->whereRaw('1 = 0'))
            ->when($search, fn ($query) => $query->where('title', 'like', '%'.$this->escapeLike($search).'%')
                ->orWhere('slug', 'like', '%'.$this->escapeLike($search).'%'))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($lang, fn ($query) => $query->where('lang', $lang))
            ->when($authorId, fn ($query) => $query->where('author_id', $authorId))
            ->when($sort === 'title', fn ($query) => $query->orderBy('title', $direction))
            ->when($sort === 'author', fn ($query) => $query
                ->select('news.*')
                ->leftJoin('news_authors', 'news_authors.id', '=', 'news.author_id')
                ->orderBy('news_authors.name', $direction))
            ->when($sort === 'organization', fn ($query) => $query
                ->select('news.*')
                ->leftJoin('organization', 'organization.id', '=', 'news.organization_id')
                ->orderBy('organization.name', $direction))
            ->when($sort === 'status', fn ($query) => $query->orderBy('status', $direction))
            ->when($sort === 'published_at', fn ($query) => $query->orderByDesc('published_at'))
            ->orderByDesc('news.id')
            ->paginate(25)
            ->withQueryString();

        return view($this->viewName('index'), [
            'organization' => $organization,
            'news' => $news,
            'search' => $search ?? '',
            'status' => $status ?? '',
            'lang' => $lang ?? '',
            'sort' => $sort ?? 'published_at',
            'direction' => $direction,
            'routePrefix' => $this->routePrefix(),
            'canEditArticle' => $canEditArticle,
            'canCreate' => match (true) {
                (bool) $organization => $access->hasOrganizationPermission($user, $organization, 'news.create', 'organization.news.edit'),
                $this->isPersonalDashboard() => true,
                default => $user->can('news.action.create'),
            },
        ]);
    }

    public function create(Request $request, ?Organization $organization = null): View|RedirectResponse
    {
        $options = $this->attributionOptions($request, $organization);

        abort_unless($this->hasAnyAttributionOption($options), 403);

        if (! $request->user()->newsAuthor) {
            // Author profile management is personal, not organization-scoped —
            // always sent to the same admin page regardless of context (an
            // organization-only member can still reach /admin via
            // access-admin, see AppServiceProvider).
            return redirect()->route('admin.news.authors.index')->with('status', 'author-profile-required');
        }

        return view($this->viewName('create'), [
            'organization' => $organization,
            'routePrefix' => $this->routePrefix(),
            ...$this->formData(null, $options, $organization),
        ]);
    }

    public function searchRelations(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(['players', 'teams', 'tournaments', 'staff', 'organizations'])],
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $term = '%'.$this->escapeLike($validated['q']).'%';

        $results = match ($validated['type']) {
            'players' => Player::where('handle', 'like', $term)->limit(10)->get(['id', 'handle as label']),
            'teams' => Team::where('name', 'like', $term)->limit(10)->get(['id', 'name as label']),
            'tournaments' => Tournament::where('name', 'like', $term)->limit(10)->get(['id', 'name as label']),
            'staff' => Staff::where('handle', 'like', $term)->limit(10)->get(['id', 'handle as label']),
            'organizations' => Organization::where('name', 'like', $term)->limit(10)->get(['id', 'name as label']),
        };

        return response()->json($results);
    }

    public function store(Request $request, ?Organization $organization = null): RedirectResponse
    {
        $options = $this->attributionOptions($request, $organization);

        abort_unless($this->hasAnyAttributionOption($options), 403);

        $author = $request->user()->newsAuthor;
        abort_unless($author, 403);

        $validated = $this->validated($request, null, $options, $organization);
        $validated['slug'] = ($validated['slug'] ?? null) ?: Str::slug($validated['title']);
        $validated['author_id'] = $author->id;
        $validated['status'] = 'draft';

        if (! $request->user()->can('news.edit')) {
            unset($validated['is_featured'], $validated['show_on_home']);
        }

        $article = News::create($validated);

        $this->syncRelations($article, $request);

        activity('publisher')->performedOn($article)->causedBy($request->user())
            ->withProperties(ActivityChangeSet::fromCreated($article, ['title', 'slug', 'lang', 'organization_id', 'status'])->toArray())
            ->log('news.created');

        return redirect()->route($this->routePrefix().'edit', $organization ? [$organization, $article] : $article)
            ->with('status', 'article-created');
    }

    public function edit(Request $request, ?Organization $organization = null, ?News $article = null): View
    {
        $article = $this->requiredArticle($article);
        $this->ensureBelongsToOrganization($article, $organization);
        $this->ensureCanManageArticle($request, $article, 'news.edit', 'organization.news.edit', 'news.author');

        $restricted = ! $request->user()->can('news.edit');
        $options = $organization
            ? $this->attributionOptions($request, $organization)
            : ($restricted ? $this->attributionOptions($request) : ['organizationIds' => null, 'personal' => true]);

        return view($this->viewName('edit'), [
            'organization' => $organization,
            'routePrefix' => $this->routePrefix(),
            ...$this->formData($article, $options, $organization),
            'canPublish' => $this->canManageArticle($request, $article, 'news.publish', 'organization.news.publish', 'news.author'),
            'canPublishUnvalidated' => $this->canManageArticle($request, $article, 'news.publish.unvalidated', 'organization.news.publish.unvalidated'),
            'canArchive' => $this->canManageArticle($request, $article, 'news.delete', 'organization.news.delete', 'news.author'),
            'canValidate' => $article->organization_id
                ? $this->canManageArticle($request, $article, 'news.validate', 'organization.news.validate')
                : false,
            'canComment' => $article->organization_id
                ? $this->canManageArticle($request, $article, 'news.edit', 'organization.news.edit')
                : false,
            'comments' => $article->comments()->with(['user', 'resolver'])->orderBy('created_at')->get(),
        ]);
    }

    public function update(Request $request, ?Organization $organization = null, ?News $article = null): RedirectResponse
    {
        $article = $this->requiredArticle($article);
        $this->ensureBelongsToOrganization($article, $organization);
        $this->ensureCanManageArticle($request, $article, 'news.edit', 'organization.news.edit', 'news.author');

        $restricted = ! $request->user()->can('news.edit');
        $options = $organization
            ? $this->attributionOptions($request, $organization)
            : ($restricted ? $this->attributionOptions($request) : ['organizationIds' => null, 'personal' => true]);

        $validated = $this->validated($request, $article, $options, $organization);
        $validated['slug'] = ($validated['slug'] ?? null) ?: Str::slug($validated['title']);

        if ($restricted) {
            unset($validated['is_featured'], $validated['show_on_home']);
        }

        $wasValidated = $article->validated_at !== null;
        if ($wasValidated) {
            $validated['validated_at'] = null;
            $validated['validated_by'] = null;
        }

        $article->update($validated);

        if ($wasValidated) {
            $article->comments()->create([
                'user_id' => $request->user()->id,
                'type' => 'system',
                'body' => __('admin.news.review.validation_reset_system_message'),
            ]);
        }

        $this->syncRelations($article, $request);

        // content is excluded from the diff — HTML article body, too large
        // and noisy to be useful as an activity log field.
        activity('publisher')->performedOn($article)->causedBy($request->user())
            ->withProperties(ActivityChangeSet::fromModel($article, array_diff(array_keys($validated), ['content']))->toArray())
            ->log('news.updated');

        return back()->with('status', 'article-updated');
    }

    public function destroy(Request $request, ?Organization $organization = null, ?News $article = null): RedirectResponse
    {
        $article = $this->requiredArticle($article);
        $this->ensureBelongsToOrganization($article, $organization);
        $this->ensureCanManageArticle($request, $article, 'news.delete', 'organization.news.delete', 'news.author');

        $title = $article->title;
        $article->delete();

        activity('publisher')->causedBy($request->user())
            ->withProperties(['title' => $title, 'organization_id' => $article->organization_id])
            ->log('news.deleted');

        return redirect()->route($this->routePrefix().'index', $organization ?? [])->with('status', 'article-deleted');
    }

    public function publish(Request $request, ?Organization $organization = null, ?News $article = null): RedirectResponse
    {
        $article = $this->requiredArticle($article);
        $this->ensureBelongsToOrganization($article, $organization);
        $this->ensureCanManageArticle($request, $article, 'news.publish', 'organization.news.publish', 'news.author');

        if ($article->organization_id && ! $article->validated_at) {
            $this->ensureCanManageArticle($request, $article, 'news.publish.unvalidated', 'organization.news.publish.unvalidated');
        }

        $article->update([
            'status' => 'published',
            'published_at' => $article->published_at ?? now(),
            'scheduled_at' => null,
        ]);

        activity('publisher')->performedOn($article)->causedBy($request->user())
            ->withProperties(ActivityChangeSet::fromModel($article, ['status', 'published_at'])->toArray())
            ->log('news.published');

        return back()->with('status', 'article-published');
    }

    /**
     * The "soft delete" for an article — same permission as destroy(),
     * since it's an alternative to deleting rather than a lighter action.
     */
    public function archive(Request $request, ?Organization $organization = null, ?News $article = null): RedirectResponse
    {
        $article = $this->requiredArticle($article);
        $this->ensureBelongsToOrganization($article, $organization);
        $this->ensureCanManageArticle($request, $article, 'news.delete', 'organization.news.delete', 'news.author');

        $article->update(['status' => 'archived']);

        activity('publisher')->performedOn($article)->causedBy($request->user())
            ->withProperties(ActivityChangeSet::fromModel($article, ['status'])->toArray())
            ->log('news.archived');

        return back()->with('status', 'article-archived');
    }

    /**
     * Marks an organization-attributed article as reviewed/approved —
     * organization-only, since the review workflow (see this class's
     * docblock) doesn't apply to personal articles. Any subsequent edit
     * clears this again (see update()).
     */
    public function markValidated(Request $request, ?Organization $organization = null, ?News $article = null): RedirectResponse
    {
        $article = $this->requiredArticle($article);
        $this->ensureBelongsToOrganization($article, $organization);
        abort_unless($article->organization_id, 404);
        $this->ensureCanManageArticle($request, $article, 'news.validate', 'organization.news.validate');

        $article->update(['validated_at' => now(), 'validated_by' => $request->user()->id]);

        activity('organization')->performedOn($article)->causedBy($request->user())
            ->withProperties(['organization_id' => $article->organization_id])
            ->log('news.validated');

        return back()->with('status', 'article-validated');
    }

    public function storeComment(Request $request, ?Organization $organization = null, ?News $article = null): RedirectResponse
    {
        $article = $this->requiredArticle($article);
        $this->ensureBelongsToOrganization($article, $organization);
        abort_unless($article->organization_id, 404);
        $this->ensureCanManageArticle($request, $article, 'news.edit', 'organization.news.edit');

        $validated = $request->validate([
            'field' => ['nullable', 'string', Rule::in(self::REVIEWABLE_FIELDS)],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $article->comments()->create([
            'user_id' => $request->user()->id,
            'field' => $validated['field'] ?? null,
            'body' => $validated['body'],
            'type' => 'comment',
        ]);

        activity('organization')->performedOn($article)->causedBy($request->user())
            ->withProperties(['organization_id' => $article->organization_id])
            ->log('news.comment_added');

        return back()->with('status', 'comment-added');
    }

    public function resolveComment(Request $request, ?Organization $organization = null, ?News $article = null, ?NewsComment $comment = null): RedirectResponse
    {
        $article = $this->requiredArticle($article);
        abort_unless($comment, 404);
        $this->ensureBelongsToOrganization($article, $organization);
        abort_unless($comment->news_id === $article->id, 404);
        $this->ensureCanManageArticle($request, $article, 'news.edit', 'organization.news.edit');

        $comment->update(['resolved_at' => now(), 'resolved_by' => $request->user()->id]);

        return back()->with('status', 'comment-resolved');
    }

    public function destroyComment(Request $request, ?Organization $organization = null, ?News $article = null, ?NewsComment $comment = null): RedirectResponse
    {
        $article = $this->requiredArticle($article);
        abort_unless($comment, 404);
        $this->ensureBelongsToOrganization($article, $organization);
        abort_unless($comment->news_id === $article->id, 404);

        $canModerate = $this->canManageArticle($request, $article, 'news.delete', 'organization.news.delete');
        abort_unless($comment->user_id === $request->user()->id || $canModerate, 403);

        $comment->delete();

        return back()->with('status', 'comment-removed');
    }

    public function toggleFeature(Request $request, News $article): RedirectResponse
    {
        $article->update(['is_featured' => ! $article->is_featured]);

        activity('publisher')->performedOn($article)->causedBy($request->user())
            ->withProperties(ActivityChangeSet::fromModel($article, ['is_featured'])->toArray())
            ->log('news.feature_toggled');

        return back()->with('status', 'article-updated');
    }

    public function toggleShowOnHome(Request $request, News $article): RedirectResponse
    {
        $article->update(['show_on_home' => ! $article->show_on_home]);

        activity('publisher')->performedOn($article)->causedBy($request->user())
            ->withProperties(ActivityChangeSet::fromModel($article, ['show_on_home'])->toArray())
            ->log('news.show_on_home_toggled');

        return back()->with('status', 'article-updated');
    }

    /**
     * What the current user may attribute an article to: `organizationIds`
     * is null when unrestricted (site editor with news.create/news.edit),
     * otherwise the specific ids they hold the matching role on. `personal`
     * is whether they may save an article with none set (site editor, or
     * the standalone 'news.author' permission).
     *
     * In dashboard mode ($organization bound from the URL) attribution
     * isn't a choice at all — every article managed from an organization's
     * own dashboard belongs to that organization, full stop.
     *
     * @return array{organizationIds: ?Collection<int,int>, personal: bool}
     */
    private function attributionOptions(Request $request, ?Organization $organization = null): array
    {
        if ($organization) {
            $canAttribute = app(OrganizationAccessService::class)
                ->hasOrganizationPermission($request->user(), $organization, 'news.create', 'organization.news.edit');

            return ['organizationIds' => $canAttribute ? collect([$organization->id]) : collect(), 'personal' => false];
        }

        $user = $request->user();
        $unrestricted = $user->can('news.create') || $user->can('news.edit');

        return [
            'organizationIds' => $unrestricted ? null : $this->allowedOrganizationIds($request, 'organization.news.edit'),
            'personal' => $unrestricted || $user->can('news.author'),
        ];
    }

    private function hasAnyAttributionOption(array $options): bool
    {
        return $options['organizationIds'] === null
            || $options['personal']
            || $options['organizationIds']->isNotEmpty();
    }

    /**
     * @param  array{organizationIds: ?Collection<int,int>, personal: bool}  $options
     */
    private function validated(Request $request, ?News $article, array $options, ?Organization $organization = null): array
    {
        $validated = $request->validate([
            'organization_id' => ['nullable', 'integer', 'exists:organization,id'],
            'lang' => ['required', 'string', 'max:5'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('news', 'slug')->ignore($article?->id)],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'is_featured' => ['sometimes', 'boolean'],
            'show_on_home' => ['sometimes', 'boolean'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
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

        if ($organization) {
            // Dashboard mode: attribution isn't a user choice, see
            // attributionOptions() — force it regardless of what was
            // submitted rather than trusting a hidden field.
            $validated['organization_id'] = $organization->id;

            return $validated;
        }

        $organizationId = $validated['organization_id'] ?? null;
        $organizationUnchanged = $article && (int) $organizationId === (int) $article->organization_id;

        if ($options['organizationIds'] !== null && $organizationId && ! $organizationUnchanged) {
            abort_unless($options['organizationIds']->contains($organizationId), 403);
        }

        if (! $organizationId && ! $organizationUnchanged) {
            abort_unless($options['personal'], 403);
        }

        return $validated;
    }

    private function syncRelations(News $article, Request $request): void
    {
        $article->players()->sync($request->input('players', []));
        $article->teams()->sync($request->input('teams', []));
        $article->tournaments()->sync($request->input('tournaments', []));
        $article->staff()->sync($request->input('staff', []));
        $article->organizations()->sync($request->input('organizations', []));
    }

    /**
     * @param  array{organizationIds: ?Collection<int,int>, personal: bool}  $options
     */
    private function formData(?News $article, array $options, ?Organization $organization = null): array
    {
        return [
            'article' => $article,
            // In dashboard mode there's nothing to pick from — the form
            // shows the fixed organization name instead of a dropdown, see
            // resources/views/news/_form.blade.php.
            'organizations' => $organization ? collect() : Organization::query()
                ->when($options['organizationIds'] !== null, fn ($query) => $query->whereIn('id', $options['organizationIds']))
                ->orderBy('name')->get(['id', 'name']),
            'canAttributePersonally' => $options['personal'],
            'selectedPlayers' => $article?->players()->get(['players.id', 'players.handle as label']) ?? collect(),
            'selectedTeams' => $article?->teams()->get(['teams.id', 'teams.name as label']) ?? collect(),
            'selectedTournaments' => $article?->tournaments()->get(['tournaments.id', 'tournaments.name as label']) ?? collect(),
            'selectedStaff' => $article?->staff()->get(['staff.id', 'staff.handle as label']) ?? collect(),
            'selectedOrganizations' => $article?->organizations()->get(['organization.id', 'organization.name as label']) ?? collect(),
            'images' => $article ? $article->images()->orderByDesc('created_at')->get() : collect(),
        ];
    }

    /**
     * An article reaching here belonging to a *different* organization than
     * the one bound in the dashboard URL 404s rather than being viewed or
     * mutated under the wrong context — mirrors
     * Organization\RoleController::ensureBelongsToOrganization(). No-op in
     * admin mode ($organization null), where cross-organization access is
     * already the point.
     */
    private function ensureBelongsToOrganization(News $article, ?Organization $organization): void
    {
        if ($organization) {
            abort_unless((int) $article->organization_id === $organization->id, 404);
        }
    }

    /**
     * $article is only nullable in these method signatures so that its
     * position can match $organization's — see this class's docblock and
     * Admin\MatchStreamController::store()'s $tournament docblock for the
     * same "unused-but-positionally-required" idea applied to an optional
     * parameter instead: Laravel's implicit route-model-binding dispatch
     * passes bound models positionally in the order they appear in the
     * *route*, not the order they're type-hinted in the method — so
     * $organization and $article must be declared in the same order they
     * appear in the URL (organization first) for both the admin route
     * (no {organization} segment at all) and the dashboard route (both
     * segments) to bind correctly. $article itself is always genuinely
     * present whenever a route actually reaches these methods.
     */
    private function requiredArticle(?News $article): News
    {
        abort_unless($article, 404);

        return $article;
    }
}
