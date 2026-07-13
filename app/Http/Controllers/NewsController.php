<?php

/**
 * GC-Stats — News controller
 *
 * Handles the public news article page, the author profile page (listing
 * all published articles by that author) and the publisher page (listing
 * all published articles from that outlet).
 *
 * Only published articles are ever shown publicly.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\NewsAuthor;
use App\Models\NewsPublisher;
use Illuminate\Support\Facades\Cache;

class NewsController extends Controller
{
    public function show(string $slug)
    {
        $locale = app()->getLocale();

        $data = Cache::remember("news_show_{$slug}_{$locale}", now()->addHours(6), function () use ($slug) {
            $news = News::with(['author.currentLogo', 'publisher.currentLogo', 'players', 'teams', 'tournaments'])
                ->where('slug', $slug)
                ->where('status', 'published')
                ->firstOrFail();

            return [
                'title' => $news->title,
                'lang' => $news->lang,
                'excerpt' => $news->excerpt,
                'content' => $news->content,
                'imageCover' => $news->image_cover,
                'date' => $news->published_at?->translatedFormat('d F Y'),
                'author' => $news->author ? [
                    'name' => $news->author->name,
                    'slug' => $news->author->slug,
                    'bio' => $news->author->bio,
                    'logo' => $news->author->currentLogo
                        ? asset('storage/authors/'.$news->author->currentLogo->id.'/200x200.webp')
                        : null,
                    'socials' => $news->author->socials,
                ] : null,
                'publisher' => $news->publisher ? [
                    'name' => $news->publisher->name,
                    'slug' => $news->publisher->slug,
                    'logo' => $news->publisher->currentLogo
                        ? asset('storage/publishers/'.$news->publisher->currentLogo->id.'/200x200.webp')
                        : null,
                    'socials' => $news->publisher->socials,
                ] : null,
                'players' => $news->players->map(fn ($p) => ['id' => $p->id, 'handle' => $p->handle])->all(),
                'teams' => $news->teams->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->all(),
                'tournaments' => $news->tournaments->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->all(),
            ];
        });

        return response()
            ->view('news.show', $data)
            ->header('Cache-Control', 'public, max-age=21600, s-maxage=21600')
            ->header('Vary', 'Accept-Language');
    }

    public function author(string $slug)
    {
        $author = Cache::remember("news_author_{$slug}", now()->addDay(), function () use ($slug) {
            $model = NewsAuthor::with('currentLogo')->where('slug', $slug)->firstOrFail();

            return [
                'id' => $model->id,
                'name' => $model->name,
                'slug' => $model->slug,
                'bio' => $model->bio,
                'logo' => $model->currentLogo
                    ? asset('storage/authors/'.$model->currentLogo->id.'/200x200.webp')
                    : null,
                'socials' => $model->socials,
            ];
        });

        $articles = News::with(['publisher.currentLogo', 'author.currentLogo'])
            ->where('author_id', $author['id'])
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->paginate(12);

        return response()
            ->view('news.author', compact('author', 'articles'))
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600')
            ->header('Vary', 'Accept-Language');
    }

    public function publisher(string $slug)
    {
        $publisher = Cache::remember("news_publisher_{$slug}", now()->addDay(), function () use ($slug) {
            $model = NewsPublisher::with('currentLogo')->where('slug', $slug)->firstOrFail();

            return [
                'id' => $model->id,
                'name' => $model->name,
                'slug' => $model->slug,
                'logo' => $model->currentLogo
                    ? asset('storage/publishers/'.$model->currentLogo->id.'/200x200.webp')
                    : null,
                'socials' => $model->socials,
            ];
        });

        $articles = News::with(['author.currentLogo', 'publisher.currentLogo'])
            ->where('publisher_id', $publisher['id'])
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->paginate(12);

        return response()
            ->view('news.publisher', compact('publisher', 'articles'))
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600')
            ->header('Vary', 'Accept-Language');
    }
}
