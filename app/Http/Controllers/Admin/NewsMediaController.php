<?php

/**
 * GC-Stats — Admin: news media library
 *
 * Upload/browse/delete news cover images (App\Models\NewsImage) and link
 * them to an article. Reuses the same App\Services\LogoUploadService storage
 * logic as the internal API's Api\ApiNewsImageController — see its docblock.
 * Reachable by site editors (news.media.*) or a publisher's own member
 * managing their publisher's media — see
 * App\Http\Controllers\Concerns\ManagesPublisherScopedNews. Having a
 * NewsAuthor profile grants no media capability by itself.
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
use App\Models\NewsImage;
use App\Services\LogoUploadService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NewsMediaController extends Controller
{
    use ManagesPublisherScopedNews;

    public function index(Request $request): View
    {
        $user = $request->user();
        $allowedPublisherIds = $user->can('news.media.view') ? null : $this->allowedPublisherIds($request, 'publisher.media.view');

        abort_if($allowedPublisherIds !== null && $allowedPublisherIds->isEmpty(), 403);

        $unattachedOnly = $request->boolean('unattached');

        $images = NewsImage::query()
            ->with(['author', 'news'])
            ->when($allowedPublisherIds !== null, fn ($query) => $query->where(function ($query) use ($allowedPublisherIds) {
                // Unattached uploads have no `news` row to scope by yet —
                // without this branch, an image is invisible to its own
                // uploader from the moment it's uploaded until it's linked.
                $query->whereNull('news_id')
                    ->orWhereHas('news', fn ($query) => $query->whereIn('publisher_id', $allowedPublisherIds));
            }))
            ->when($unattachedOnly, fn ($query) => $query->whereNull('news_id'))
            ->orderByDesc('created_at')
            ->paginate(40)
            ->withQueryString();

        $linkablePublisherIds = $user->can('news.media.upload') ? null : $this->allowedPublisherIds($request, 'publisher.media.upload');

        return view('admin.news.media.index', [
            'images' => $images,
            'unattachedOnly' => $unattachedOnly,
            'deletablePublisherIds' => $user->can('news.media.delete') ? collect() : $this->allowedPublisherIds($request, 'publisher.media.delete'),
            'editablePublisherIds' => $user->can('news.edit') ? collect() : $this->allowedPublisherIds($request, 'publisher.news.edit'),
            'linkableArticles' => News::query()
                ->when($linkablePublisherIds !== null, fn ($query) => $query->whereIn('publisher_id', $linkablePublisherIds))
                ->orderByDesc('id')
                ->limit(300)
                ->get(['id', 'title']),
        ]);
    }

    public function store(Request $request, LogoUploadService $logoUploadService): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->can('news.media.upload') || $this->allowedPublisherIds($request, 'publisher.media.upload')->isNotEmpty(), 403);

        $validated = $request->validate([
            'image' => ['required', 'file', 'image', 'max:10240'],
            'news_id' => ['nullable', 'integer', 'exists:news,id'],
        ]);

        if (! empty($validated['news_id'])) {
            $this->ensureCanManageArticle($request, News::findOrFail($validated['news_id']), 'news.media.upload', 'publisher.media.upload');
        }

        $uuid = (string) Str::uuid();

        $logoUploadService->storeImage($validated['image'], "news/{$uuid}/cover.webp", 1400, null, 85);

        NewsImage::create([
            'id' => $uuid,
            'news_id' => $validated['news_id'] ?? null,
        ]);

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
                $this->ensureCanManageArticle($request, $image->news, 'news.media.upload', 'publisher.media.upload');
            }

            $image->update(['news_id' => null]);

            return back()->with('status', 'media-unlinked');
        }

        // Re-linking an image already attached elsewhere also requires
        // being able to manage its *current* article, not just the target
        // one — otherwise a publisher member could re-point another
        // publisher's media onto their own article by guessing its id.
        if ($image->news) {
            $this->ensureCanManageArticle($request, $image->news, 'news.media.upload', 'publisher.media.upload');
        }

        $article = News::findOrFail($validated['news_id']);
        $this->ensureCanManageArticle($request, $article, 'news.media.upload', 'publisher.media.upload');

        $image->update(['news_id' => $article->id]);

        return back()->with('status', 'media-linked');
    }

    public function setCover(Request $request, News $article, NewsImage $image): RedirectResponse
    {
        $this->ensureCanManageArticle($request, $article, 'news.edit', 'publisher.news.edit');

        abort_unless($image->news_id === $article->id, 404);

        $article->update(['image_cover' => $image->url]);

        return back()->with('status', 'cover-updated');
    }

    public function destroy(Request $request, NewsImage $image, LogoUploadService $logoUploadService): RedirectResponse
    {
        if (! $request->user()->can('news.media.delete')) {
            abort_unless($image->news, 403);
            $this->ensureCanManageArticle($request, $image->news, 'news.media.delete', 'publisher.media.delete');
        }

        $logoUploadService->deleteFiles('news', $image->id);
        $image->delete();

        return back()->with('status', 'media-deleted');
    }
}
