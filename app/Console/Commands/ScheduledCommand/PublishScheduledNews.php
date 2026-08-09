<?php

/**
 * GC-Stats — Scheduled news publishing command
 *
 * Flips an article to `published` right as its scheduled_at hits — mirrors
 * ActivateLiveMatches: only articles scheduled within the last 5 minutes are
 * eligible, so this never sweeps up a scheduled_at that's simply drifted
 * into the past (a server hiccup, a paused queue) beyond that window; it
 * only catches the publish times the command's own per-minute schedule is
 * actually watching for in real time. An unvalidated organization-attributed
 * article is published anyway once its scheduled time hits — scheduling it
 * in the first place already required the news.publish.unvalidated /
 * organization.news.publish.unvalidated permission (see
 * Admin\NewsController::update()), same as a manual unvalidated publish.
 * Usage: php artisan news:publish-scheduled
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Console\Commands\ScheduledCommand;

use App\Models\News;
use Illuminate\Console\Command;

class PublishScheduledNews extends Command
{
    protected $signature = 'news:publish-scheduled';

    protected $description = 'Publish articles whose scheduled publish time has just passed';

    public function handle(): int
    {
        $articles = News::query()
            ->whereIn('status', ['draft'])
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [now()->subMinutes(5), now()])
            ->get();

        if ($articles->isEmpty()) {
            $this->info('No article to publish.');

            return self::SUCCESS;
        }

        foreach ($articles as $article) {
            $article->update([
                'status' => 'published',
                'published_at' => $article->published_at ?? $article->scheduled_at,
                'scheduled_at' => null,
            ]);

            activity('publisher')->performedOn($article)
                ->withProperties(['status' => 'published'])
                ->log('news.auto_published');

            $this->info("Article #{$article->id} published.");
        }

        return self::SUCCESS;
    }
}
