<?php

/**
 * GC-Stats — News model observer
 *
 * Purges CDN cache and flushes related app-level caches (home news feed,
 * article page, author/publisher pages) whenever a news article is saved
 * or deleted, so edits and status changes show up immediately on the
 * homepage and on any player/team/tournament page it's linked to.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Observers;

use App\Models\News;
use App\Services\BunnyCache;
use Illuminate\Support\Facades\Cache;

class NewsObserver
{
    public function saved(News $news): void
    {
        $this->purge($news);
    }

    public function deleting(News $news): void
    {
        $this->purge($news);
    }

    private function purge(News $news): void
    {
        $slugs = array_unique(array_filter([$news->slug, $news->getOriginal('slug')]));

        $news->loadMissing(['author', 'publisher', 'players', 'teams', 'tournaments']);

        $baseUrl = rtrim((string) config('app.url'), '/');

        $urls = array_map(fn ($slug) => "{$baseUrl}/news/{$slug}", $slugs);
        $urls[] = "{$baseUrl}/";

        if ($news->author) {
            $urls[] = "{$baseUrl}/news/author/{$news->author->slug}";
        }

        if ($news->publisher) {
            $urls[] = "{$baseUrl}/news/publisher/{$news->publisher->slug}";
        }

        foreach ($news->players as $player) {
            $urls[] = "{$baseUrl}/player/{$player->id}";
        }

        foreach ($news->teams as $team) {
            $urls[] = "{$baseUrl}/team/{$team->id}";
        }

        foreach ($news->tournaments as $tournament) {
            $urls[] = "{$baseUrl}/tournaments/{$tournament->id}";
        }

        app(BunnyCache::class)->purgeUrls($urls);

        foreach (array_keys(config('locales.supported')) as $locale) {
            Cache::forget("home_news_{$locale}");

            foreach ($slugs as $slug) {
                Cache::forget("news_show_{$slug}_{$locale}");
            }
        }

        if ($news->author) {
            Cache::forget("news_author_{$news->author->slug}");
        }

        if ($news->publisher) {
            Cache::forget("news_publisher_{$news->publisher->slug}");
        }
    }
}
