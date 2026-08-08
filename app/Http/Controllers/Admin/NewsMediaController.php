<?php

/**
 * GC-Stats — Admin: news media library
 *
 * Upload/browse/delete news cover images (App\Models\NewsImage) and link
 * them to an article. Reuses the same App\Services\LogoUploadService storage
 * logic as the internal API's Api\ApiNewsImageController — see its docblock.
 * Reachable by site editors (news.media.*) or an organization's own member
 * managing their organization's media — see
 * App\Http\Controllers\Concerns\ManagesNewsAccess. Having a
 * NewsAuthor profile grants no media capability by itself.
 *
 * Dual-context like Admin\NewsController: index() backs both
 * admin.news.media.index (flat, cross-organization) and
 * organization-dashboard.news.media.index (scoped to the {organization}
 * bound in the dashboard URL) — see that controller's docblock for the
 * general pattern. store()/link()/setCover()/destroy() don't need the same
 * treatment: they're id-based mutations that redirect back() to whichever
 * page posted to them, and their authorization (via ManagesNewsAccess) is
 * already independent of which route the request came from.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ManagesNewsAccess;
use App\Http\Controllers\Public\Controller;
use App\Models\News;
use App\Models\NewsImage;
use App\Models\Organization;
use App\Services\LogoUploadService;
use App\Services\OrganizationAccessService;
use App\Support\Activity\ActivityChangeSet;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NewsMediaController extends Controller
{
    use ManagesNewsAccess;

    private function isDashboard(): bool
    {
        return request()->routeIs('organization-dashboard.*');
    }

    private function routePrefix(): string
    {
        return $this->isDashboard() ? 'organization-dashboard.news.media.' : 'admin.news.media.';
    }

    private function viewName(string $page): string
    {
        return $this->isDashboard() ? "organization.dashboard.news.media.{$page}" : "admin.news.media.{$page}";
    }

    public function index(Request $request, ?Organization $organization = null): View
    {
        $user = $request->user();

        if ($organization) {
            $access = app(OrganizationAccessService::class);
            abort_unless($access->hasOrganizationPermission($user, $organization, 'news.media.view', 'organization.media.view'), 403);

            $allowedOrganizationIds = collect([$organization->id]);
            $canUpload = $access->hasOrganizationPermission($user, $organization, 'news.media.upload', 'organization.media.upload');
            $editableOrganizationIds = $access->hasOrganizationPermission($user, $organization, 'news.edit', 'organization.news.edit') ? collect([$organization->id]) : collect();
            $deletableOrganizationIds = $access->hasOrganizationPermission($user, $organization, 'news.media.delete', 'organization.media.delete') ? collect([$organization->id]) : collect();
            $linkableOrganizationIds = $allowedOrganizationIds;
        } else {
            $allowedOrganizationIds = $user->can('news.media.view') ? null : $this->allowedOrganizationIds($request, 'organization.media.view');
            abort_if($allowedOrganizationIds !== null && $allowedOrganizationIds->isEmpty(), 403);

            $canUpload = $user->can('news.media.action.upload');
            $editableOrganizationIds = $user->can('news.edit') ? collect() : $this->allowedOrganizationIds($request, 'organization.news.edit');
            $deletableOrganizationIds = $user->can('news.media.delete') ? collect() : $this->allowedOrganizationIds($request, 'organization.media.delete');
            $linkableOrganizationIds = $user->can('news.media.upload') ? null : $this->allowedOrganizationIds($request, 'organization.media.upload');
        }

        $unattachedOnly = $request->boolean('unattached');

        $images = NewsImage::query()
            ->with(['author', 'news'])
            ->when($allowedOrganizationIds !== null, fn ($query) => $query->where(function ($query) use ($allowedOrganizationIds) {
                // Unattached uploads have no `news` row to scope by yet —
                // without this branch, an image is invisible to its own
                // uploader from the moment it's uploaded until it's linked.
                $query->whereNull('news_id')
                    ->orWhereHas('news', fn ($query) => $query->whereIn('organization_id', $allowedOrganizationIds));
            }))
            ->when($unattachedOnly, fn ($query) => $query->whereNull('news_id'))
            ->orderByDesc('created_at')
            ->paginate(40)
            ->withQueryString();

        return view($this->viewName('index'), [
            'organization' => $organization,
            'routePrefix' => $this->routePrefix(),
            'images' => $images,
            'unattachedOnly' => $unattachedOnly,
            'canUpload' => $canUpload,
            'deletableOrganizationIds' => $deletableOrganizationIds,
            'editableOrganizationIds' => $editableOrganizationIds,
            'linkableArticles' => News::query()
                ->when($linkableOrganizationIds !== null, fn ($query) => $query->whereIn('organization_id', $linkableOrganizationIds))
                ->orderByDesc('id')
                ->limit(300)
                ->get(['id', 'title']),
        ]);
    }

    public function store(Request $request, LogoUploadService $logoUploadService): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->can('news.media.upload') || $this->allowedOrganizationIds($request, 'organization.media.upload')->isNotEmpty(), 403);

        $validated = $request->validate([
            'image' => ['required', 'file', 'image', 'max:10240'],
            'news_id' => ['nullable', 'integer', 'exists:news,id'],
        ]);

        if (! empty($validated['news_id'])) {
            $this->ensureCanManageArticle($request, News::findOrFail($validated['news_id']), 'news.media.upload', 'organization.media.upload');
        }

        $uuid = (string) Str::uuid();

        $logoUploadService->storeImage($validated['image'], "news/{$uuid}/cover.webp", 1400, null, 85);

        $image = NewsImage::create([
            'id' => $uuid,
            'news_id' => $validated['news_id'] ?? null,
        ]);

        activity('publisher')->performedOn($image)->causedBy($user)
            ->withProperties(ActivityChangeSet::fromCreated($image, ['news_id'])->toArray())
            ->log('media.uploaded');

        return back()->with('status', 'media-uploaded');
    }

    public function link(Request $request, NewsImage $image): RedirectResponse
    {
        $validated = $request->validate([
            'news_id' => ['nullable', 'integer', 'exists:news,id'],
        ]);

        if (empty($validated['news_id'])) {
            // Unlinking only requires being able to manage the image's
            // *current* article — same rule as everywhere else.
            if ($image->news) {
                $this->ensureCanManageArticle($request, $image->news, 'news.media.upload', 'organization.media.upload');
            }

            $image->update(['news_id' => null]);

            activity('publisher')->performedOn($image)->causedBy($request->user())
                ->withProperties(ActivityChangeSet::fromModel($image, ['news_id'])->toArray())
                ->log('media.unlinked');

            return back()->with('status', 'media-unlinked');
        }

        // Re-linking an image already attached elsewhere also requires
        // being able to manage its *current* article, not just the target
        // one — otherwise an organization member could re-point another
        // organization's media onto their own article by guessing its id.
        if ($image->news) {
            $this->ensureCanManageArticle($request, $image->news, 'news.media.upload', 'organization.media.upload');
        }

        $article = News::findOrFail($validated['news_id']);
        $this->ensureCanManageArticle($request, $article, 'news.media.upload', 'organization.media.upload');

        $image->update(['news_id' => $article->id]);

        activity('publisher')->performedOn($image)->causedBy($request->user())
            ->withProperties(ActivityChangeSet::fromModel($image, ['news_id'])->toArray())
            ->log('media.linked');

        return back()->with('status', 'media-linked');
    }

    public function setCover(Request $request, News $article, NewsImage $image): RedirectResponse
    {
        $this->ensureCanManageArticle($request, $article, 'news.edit', 'organization.news.edit');

        abort_unless($image->news_id === $article->id, 404);

        $article->update(['image_cover' => $image->url]);

        activity('publisher')->performedOn($article)->causedBy($request->user())
            ->withProperties(['image_id' => $image->id])
            ->log('media.cover_set');

        return back()->with('status', 'cover-updated');
    }

    public function destroy(Request $request, NewsImage $image, LogoUploadService $logoUploadService): RedirectResponse
    {
        if (! $request->user()->can('news.media.delete')) {
            abort_unless($image->news, 403);
            $this->ensureCanManageArticle($request, $image->news, 'news.media.delete', 'organization.media.delete');
        }

        $newsId = $image->news_id;
        $logoUploadService->deleteFiles('news', $image->id);
        $image->delete();

        activity('publisher')->causedBy($request->user())
            ->withProperties(['image_id' => $image->id, 'news_id' => $newsId])
            ->log('media.deleted');

        return back()->with('status', 'media-deleted');
    }
}
