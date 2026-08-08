<?php

/**
 * GC-Stats — News controller
 *
 * Handles the public news article page and the organization page (listing
 * all published articles from that organization). An author's own published articles
 * are listed on their linked user profile's "News" tab instead of a
 * standalone page — see UserProfileController::news() — but authors with no
 * linked User account (still a supported NewsAuthor state, see
 * Admin\NewsAuthorController) have no profile to redirect to, so author()
 * below keeps serving them their own page.
 *
 * Only published articles are ever shown publicly.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Concerns\ManagesNewsAccess;
use App\Models\News;
use App\Models\NewsAuthor;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NewsController extends Controller
{
    use ManagesNewsAccess;

    public function show(Request $request, string $slug)
    {
        $locale = app()->getLocale();

        $newsMeta = News::where('slug', $slug)->first(['id', 'organization_id', 'author_id', 'status', 'updated_at']);
        abort_unless($newsMeta, 404);

        $canPreview = false;
        if ($newsMeta->status !== 'published') {
            $canPreview = $request->user()
                && $this->canManageArticle($request, $newsMeta, 'news.view', 'organization.news.view');
            abort_unless($canPreview, 404);
        }

        $build = function () use ($slug) {
            $news = News::with(['author.currentLogo', 'author.user:id,username', 'organization', 'players', 'teams', 'tournaments'])
                ->where('slug', $slug)
                ->firstOrFail();

            return [
                'id' => $news->id,
                'title' => $news->title,
                'lang' => $news->lang,
                'excerpt' => $news->excerpt,
                'content' => $news->content,
                'imageCover' => $news->image_cover,
                'date' => $news->published_at?->translatedFormat('d F Y'),
                'author' => $news->author ? [
                    'name' => $news->author->name,
                    'slug' => $news->author->slug,
                    'username' => $news->author->user?->username,
                    'bio' => $news->author->bio,
                    'logo' => $news->author->currentLogo
                        ? asset('storage/authors/'.$news->author->currentLogo->id.'/200x200.webp')
                        : null,
                    'socials' => $news->author->socials,
                ] : null,
                'organization' => $news->organization ? [
                    'id' => $news->organization->id,
                    'name' => $news->organization->name,
                    'slug' => $news->organization->slug,
                    'routeSlug' => $news->organization->routeSlug(),
                    'logo' => $news->organization->logo ?: null,
                ] : null,
                'players' => $news->players->map(fn ($p) => ['id' => $p->id, 'handle' => $p->handle])->all(),
                'teams' => $news->teams->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->all(),
                'tournaments' => $news->tournaments->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->all(),
            ];
        };

        if ($canPreview) {
            return response()
                ->view('public.news.show', $build() + ['inactive_access' => true])
                ->header('Cache-Control', 'private, no-store');
        }

        $cacheKey = "news_show_{$slug}_{$locale}_{$newsMeta->updated_at->timestamp}";
        $data = Cache::remember($cacheKey, now()->addHours(6), $build);

        return response()
            ->view('public.news.show', $data + ['inactive_access' => false])
            ->header('Cache-Control', 'public, max-age=21600, s-maxage=21600')
            ->header('Vary', 'Accept-Language');
    }

    /**
     * Authors with a linked User account are canonically addressed at their
     * user profile now — redirect there. Authors with none (still a valid
     * NewsAuthor state) keep their own page, since they have nowhere else
     * to point to.
     */
    public function author(string $slug)
    {
        $model = NewsAuthor::with(['currentLogo', 'user:id,username'])->where('slug', $slug)->firstOrFail();

        if ($model->user) {
            return redirect()->route('users.news', $model->user->username, 301);
        }

        $cacheKey = "news_author_{$slug}_{$model->updated_at->timestamp}";
        $author = Cache::remember($cacheKey, now()->addDay(), fn () => [
            'id' => $model->id,
            'name' => $model->name,
            'slug' => $model->slug,
            'bio' => $model->bio,
            'logo' => $model->currentLogo
                ? asset('storage/authors/'.$model->currentLogo->id.'/200x200.webp')
                : null,
            'socials' => $model->socials,
        ]);

        $articles = News::with(['organization', 'author.currentLogo'])
            ->where('author_id', $author['id'])
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->paginate(12);

        return response()
            ->view('public.news.author', compact('author', 'articles'))
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600')
            ->header('Vary', 'Accept-Language');
    }

    /**
     * The entity News is attributed to — Organization replaces the legacy
     * NewsPublisher concept entirely (see the 0122 migration).
     */
    public function organization(int $id, ?string $slug = null)
    {
        $model = Organization::findOrFail($id);

        $canonical = $model->routeSlug();
        if ($slug !== $canonical) {
            return redirect()->route('news.organization', [$id, $canonical], 301);
        }

        $cacheKey = "news_organization_{$id}_{$model->updated_at->timestamp}";
        $organization = Cache::remember($cacheKey, now()->addDay(), fn () => [
            'id' => $model->id,
            'name' => $model->name,
            'slug' => $model->slug,
            'routeSlug' => $canonical,
            'logo' => $model->logo ?: null,
            'socials' => $model->socials,
        ]);

        $articles = News::with(['author.currentLogo', 'organization'])
            ->where('status', 'published')
            ->where(function ($query) use ($organization) {
                $query->where('organization_id', $organization['id'])
                    ->orWhereHas('organizations', fn ($q) => $q->where('organization.id', $organization['id']));
            })
            ->orderByDesc('published_at')
            ->paginate(12);

        return response()
            ->view('public.news.organization', compact('organization', 'articles'))
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600')
            ->header('Vary', 'Accept-Language');
    }
}
