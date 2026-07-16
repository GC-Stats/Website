<?php

/**
 * GC-Stats — Sitemap generator
 *
 * Artisan command that builds the public sitemap.xml file, listing static
 * pages as well as tournaments, teams, players and published news articles
 * for SEO. Individual matches are deliberately excluded — with tens of
 * thousands of them, listing each would blow crawl budget for no benefit.
 * Usage: php artisan sitemap:generate
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Console\Commands;

use App\Models\News;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Generate the sitemap.xml file';

    public function handle()
    {
        $sitemap = Sitemap::create()
            ->add(Url::create('/')->setPriority(1.0)->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY))
            ->add(Url::create(route('terms'))->setPriority(0.2)->setChangeFrequency(Url::CHANGE_FREQUENCY_YEARLY))
            ->add(Url::create(route('legal'))->setPriority(0.2)->setChangeFrequency(Url::CHANGE_FREQUENCY_YEARLY))
            ->add(Url::create(route('privacy'))->setPriority(0.2)->setChangeFrequency(Url::CHANGE_FREQUENCY_YEARLY))
            ->add(Url::create(route('data'))->setPriority(0.2)->setChangeFrequency(Url::CHANGE_FREQUENCY_YEARLY))
            ->add(Url::create(route('takedown'))->setPriority(0.2)->setChangeFrequency(Url::CHANGE_FREQUENCY_YEARLY))
            ->add(Url::create(route('help.edit_page'))->setPriority(0.2)->setChangeFrequency(Url::CHANGE_FREQUENCY_YEARLY))
            ->add(Url::create(route('help.add_tournament'))->setPriority(0.2)->setChangeFrequency(Url::CHANGE_FREQUENCY_YEARLY))
            ->add(Url::create(route('tournaments.index'))->setPriority(0.2)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY))
            ->add(Url::create(route('developers'))->setPriority(0.2)->setChangeFrequency(Url::CHANGE_FREQUENCY_YEARLY))
            ->add(Url::create(route('about'))->setPriority(0.2)->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY))
            ->add(Url::create(route('transparency'))->setPriority(0.2)->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY))
            ->add(Url::create(route('finance'))->setPriority(0.2)->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY));

        Tournament::chunk(100, function ($tournaments) use ($sitemap) {
            foreach ($tournaments as $tournament) {
                $sitemap->add(
                    Url::create(route('tournaments.show', [$tournament->id, Str::slug($tournament->name)]))
                        ->setPriority(0.9)
                        ->setLastModificationDate($tournament->updated_at)
                );
                $sitemap->add(
                    Url::create(route('tournaments.matches', [$tournament->id, Str::slug($tournament->name)]))
                        ->setPriority(0.9)
                        ->setLastModificationDate($tournament->updated_at)
                );
                $sitemap->add(
                    Url::create(route('tournaments.stats', [$tournament->id, Str::slug($tournament->name)]))
                        ->setPriority(0.9)
                        ->setLastModificationDate($tournament->updated_at)
                );
                $sitemap->add(
                    Url::create(route('tournaments.maps', [$tournament->id, Str::slug($tournament->name)]))
                        ->setPriority(0.9)
                        ->setLastModificationDate($tournament->updated_at)
                );
            }
        });

        Team::chunk(500, function ($teams) use ($sitemap) {
            foreach ($teams as $team) {
                $sitemap->add(
                    Url::create(route('teams.show', [$team->id, Str::slug($team->name)]))
                        ->setPriority(0.7)
                        ->setLastModificationDate($team->updated_at)
                );
            }
        });

        Player::chunk(500, function ($players) use ($sitemap) {
            foreach ($players as $player) {
                $sitemap->add(
                    Url::create(route('players.show', [$player->id, Str::slug($player->handle)]))
                        ->setPriority(0.6)
                );
            }
        });

        News::published()->chunk(200, function ($articles) use ($sitemap) {
            foreach ($articles as $article) {
                $sitemap->add(
                    Url::create(route('news.show', $article->slug))
                        ->setPriority(0.6)
                        ->setLastModificationDate($article->updated_at)
                );
            }
        });

        $sitemap->writeToFile(public_path('sitemap.xml'));

        $this->info('Sitemap has been generated.');
    }
}
