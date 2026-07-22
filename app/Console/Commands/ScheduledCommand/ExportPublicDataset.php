<?php

/**
 * GC-Stats — Public dataset export
 *
 * Artisan command that exports Players, Teams, Tournaments (with phases),
 * Matches (with vetos), Maps (with stats, advanced stats and rounds), Logos,
 * Finance entries and About content as public JSON datasets, and publishes
 * them to the Bunny storage zone consumed by the public front-end. Matches
 * only reference their maps by id; the map details live in maps.json.
 * Player exports omit the `val_id` and `discord_id` columns, which are
 * internal identifiers.
 * Usage: php artisan data:export-public
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Console\Commands\ScheduledCommand;

use App\Models\FinanceEntry;
use App\Models\GameMap;
use App\Models\Logo;
use App\Models\Matchs;
use App\Models\News;
use App\Models\NewsImage;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\BunnyCache;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('data:export-public')]
#[Description('Export Players, Teams, Tournaments, Matches, Maps, Logos, Finance entries, News and About content as public JSON datasets and publish them to Bunny')]
class ExportPublicDataset extends Command
{
    public function handle(BunnyCache $bunnyCache): int
    {
        ini_set('memory_limit', '1024M');

        $this->info('Starting dataset exportation');

        $exporters = [
            'players.json' => fn () => $this->exportPlayers(),
            'teams.json' => fn () => $this->exportTeams(),
            'tournaments.json' => fn () => $this->exportTournaments(),
            'matches.json' => fn () => $this->exportMatches(),
            'maps.json' => fn () => $this->exportMaps(),
            'logos.json' => fn () => $this->exportLogos(),
            'finance.json' => fn () => $this->exportFinance(),
            'news.json' => fn () => $this->exportNews(),
        ];

        $disk = Storage::disk('bunny');
        $purgeUrls = [];
        $pullZoneUrl = rtrim((string) config('services.bunny.pull_zone_url'), '/');

        foreach ($exporters as $filename => $exporter) {
            $this->info("Starting dataset export: {$filename}");

            $data = $exporter();
            $disk->put('/data/'.$filename, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            if ($pullZoneUrl !== '') {
                $this->info("Purging Bunny URL: {$pullZoneUrl}/{$filename}");
                $purgeUrls[] = "{$pullZoneUrl}/{$filename}";
            }

            $this->info("Dataset export finished: {$filename}");
        }

        $bunnyCache->purgeUrls($purgeUrls);

        $this->info('Dataset export finished');

        return self::SUCCESS;
    }

    private function exportPlayers(): array
    {
        $players = [];

        Player::with(['teams' => fn ($query) => $query
            ->select('teams.id', 'teams.name', 'teams.short_name')
            ->wherePivotNull('left_at')])
            ->orderBy('id')
            ->chunkById(500, function ($chunk) use (&$players) {
                foreach ($chunk as $player) {
                    $currentTeam = $player->teams->first();

                    $players[] = [
                        'id' => $player->id,
                        'handle' => $player->handle,
                        'first_name' => $player->first_name,
                        'last_name' => $player->last_name,
                        'country_code' => $player->country_code,
                        'bio' => $player->bio,
                        'photo' => $player->profile_photo,
                        'socials' => $player->socials,
                        'is_active' => $player->is_active,
                        'vlr_id' => $player->vlr_id,
                        'liquipedia_link' => $player->liquipedia_link,
                        'team' => $currentTeam ? [
                            'id' => $currentTeam->id,
                            'name' => $currentTeam->name,
                            'short_name' => $currentTeam->short_name,
                        ] : null,
                        'updated_at' => $player->updated_at,
                    ];
                }
            });

        return $players;
    }

    private function exportTeams(): array
    {
        $teams = [];

        Team::query()
            ->orderBy('id')
            ->chunkById(500, function ($chunk) use (&$teams) {
                foreach ($chunk as $team) {
                    $teams[] = [
                        'id' => $team->id,
                        'name' => $team->name,
                        'short_name' => $team->short_name,
                        'country_code' => $team->country_code,
                        'bio' => $team->bio,
                        'socials' => $team->socials,
                        'is_active' => $team->is_active,
                        'vlr_id' => $team->vlr_id,
                        'liquipedia_link' => $team->liquipedia_link,
                        'logo' => $team->logo,
                        'updated_at' => $team->updated_at,
                    ];
                }
            });

        return $teams;
    }

    private function exportTournaments()
    {
        return Tournament::with('phases')->get()->map(fn (Tournament $tournament) => [
            'id' => $tournament->id,
            'name' => $tournament->name,
            'region' => $tournament->region,
            'category' => $tournament->category,
            'start_date' => $tournament->start_date,
            'end_date' => $tournament->end_date,
            'location' => $tournament->location,
            'prize_pool' => $tournament->prize_pool,
            'description' => $tournament->description,
            'liquipedia_link' => $tournament->liquipedia_link,
            'status' => $tournament->status,
            'active' => $tournament->active,
            'logo' => $tournament->logo,
            'phases' => $tournament->phases->map(fn ($phase) => [
                'id' => $phase->id,
                'name' => $phase->name,
                'format' => $phase->format,
                'order' => $phase->order,
                'parent_id' => $phase->parent_id,
            ])->values(),
            'updated_at' => $tournament->updated_at,
        ])->values();
    }

    private function exportMatches(): array
    {
        $matches = [];

        Matchs::query()
            ->select(['id', 'tournament_id', 'phase_id', 'team_a_id', 'team_b_id', 'scheduled_at', 'status', 'team_a_score', 'team_b_score', 'best_of', 'patch', 'match_order', 'round_name', 'round_number'])
            ->with([
                'game_maps:id,match_id',
                'map_bans:id,match_id,team_id,map_name,type,side,side_picked_by,order',
            ])
            ->orderBy('id')
            ->chunkById(500, function ($chunk) use (&$matches) {
                foreach ($chunk as $match) {
                    $matches[] = [
                        'id' => $match->id,
                        'tournament_id' => $match->tournament_id,
                        'phase_id' => $match->phase_id,
                        'team_a_id' => $match->team_a_id,
                        'team_b_id' => $match->team_b_id,
                        'scheduled_at' => $match->scheduled_at,
                        'status' => $match->status,
                        'team_a_score' => $match->team_a_score,
                        'team_b_score' => $match->team_b_score,
                        'best_of' => $match->best_of,
                        'patch' => $match->patch,
                        'match_order' => $match->match_order,
                        'round_name' => $match->round_name,
                        'round_number' => $match->round_number,
                        'maps' => $match->game_maps->pluck('id')->values(),
                        'vetos' => $match->map_bans->map(fn ($veto) => [
                            'team_id' => $veto->team_id,
                            'map_name' => $veto->map_name,
                            'type' => $veto->type,
                            'side' => $veto->side,
                            'side_picked_by' => $veto->side_picked_by,
                            'order' => $veto->order,
                        ])->values(),
                    ];
                }
            });

        return $matches;
    }

    private function exportMaps(): array
    {
        $maps = [];

        GameMap::query()
            ->select(['id', 'match_id', 'map_name', 'team_a_score', 'team_b_score', 'order', 'is_completed'])
            ->with([
                'playerStats:id,game_map_id,player_id,team_id,agent_name,kills,deaths,assists,acs,adr,kast_percentage,first_kills,first_deaths,headshot_percentage',
                'rounds:id,game_map_id,round_number,winning_team,win_type',
                'rounds.kills:id,game_map_round_id,killer_player_id,victim_player_id,time_ms,weapon,damage_type,is_secondary_fire,assistant_player_ids',
                'rounds.damages:id,game_map_round_id,attacker_player_id,receiver_player_id,damage,headshots,bodyshots,legshots',
                'advancedStats:id,game_map_id,player_id,agent_name,clutch_1v1_won,clutch_1v1_total,clutch_1v2_won,clutch_1v2_total,clutch_1v3_won,clutch_1v3_total,clutch_1v4_won,clutch_1v4_total,clutch_1v5_won,clutch_1v5_total,multikill_2k,multikill_3k,multikill_4k,multikill_5k,trade_kills,traded_deaths,plants,defuses,pistol_won,pistol_played,eco_won,eco_played,force_won,force_played,full_buy_won,full_buy_played,post_plant_won,post_plant_played,atk_rounds,atk_rounds_won,atk_kills,atk_kast_percentage,def_rounds,def_rounds_won,def_kills,def_kast_percentage',
            ])
            ->orderBy('id')
            ->chunkById(500, function ($chunk) use (&$maps) {
                foreach ($chunk as $map) {
                    $maps[] = [
                        'id' => $map->id,
                        'match_id' => $map->match_id,
                        'map_name' => $map->map_name,
                        'team_a_score' => $map->team_a_score,
                        'team_b_score' => $map->team_b_score,
                        'order' => $map->order,
                        'is_completed' => $map->is_completed,
                        'stats' => $map->playerStats->map(fn ($stat) => [
                            'player_id' => $stat->player_id,
                            'team_id' => $stat->team_id,
                            'agent_name' => $stat->agent_name,
                            'kills' => $stat->kills,
                            'deaths' => $stat->deaths,
                            'assists' => $stat->assists,
                            'acs' => $stat->acs,
                            'adr' => $stat->adr,
                            'kast_percentage' => $stat->kast_percentage,
                            'first_kills' => $stat->first_kills,
                            'first_deaths' => $stat->first_deaths,
                            'headshot_percentage' => $stat->headshot_percentage,
                        ])->values(),
                        'advanced_stats' => $map->advancedStats->map(fn ($stat) => collect($stat->toArray())
                            ->except(['id', 'game_map_id', 'created_at', 'updated_at'])
                            ->all())->values(),
                        'rounds' => $map->rounds->map(fn ($round) => [
                            'round_number' => $round->round_number,
                            'winning_team' => $round->winning_team,
                            'win_type' => $round->win_type,
                            'kills' => $round->kills->map(fn ($kill) => [
                                'killer_player_id' => $kill->killer_player_id,
                                'victim_player_id' => $kill->victim_player_id,
                                'time_ms' => $kill->time_ms,
                                'weapon' => $kill->weapon,
                                'damage_type' => $kill->damage_type,
                                'is_secondary_fire' => $kill->is_secondary_fire,
                                'assistant_player_ids' => $kill->assistant_player_ids,
                            ])->values(),
                            'damages' => $round->damages->map(fn ($damage) => [
                                'attacker_player_id' => $damage->attacker_player_id,
                                'receiver_player_id' => $damage->receiver_player_id,
                                'damage' => $damage->damage,
                                'headshots' => $damage->headshots,
                                'bodyshots' => $damage->bodyshots,
                                'legshots' => $damage->legshots,
                            ])->values(),
                        ])->values(),
                    ];
                }
            });

        return $maps;
    }

    private function exportLogos(): array
    {
        $logos = [];

        Logo::query()
            ->orderBy('id')
            ->chunkById(500, function ($chunk) use (&$logos) {
                foreach ($chunk as $logo) {
                    $logos[] = [
                        'id' => $logo->id,
                        'entity_type' => $logo->entity_type,
                        'entity_id' => $logo->entity_id,
                        'from' => $logo->from,
                        'until' => $logo->until,
                        'small' => Storage::disk('public')->url("{$logo->entity_type}s/{$logo->id}/200x200.webp"),
                        'full' => Storage::disk('public')->url("{$logo->entity_type}s/{$logo->id}/full.webp"),
                    ];
                }
            });

        return $logos;
    }

    private function exportFinance()
    {
        return FinanceEntry::orderByDesc('entry_date')->orderByDesc('id')->get()->map(fn (FinanceEntry $entry) => [
            'id' => $entry->id,
            'entry_date' => $entry->entry_date,
            'type' => $entry->type,
            'category' => $entry->category,
            'label' => $entry->label,
            'description' => $entry->description,
            'amount_usd' => $entry->amount_usd,
            'amount_eur' => $entry->amount_eur,
            'source_url' => $entry->source_url,
            'updated_at' => $entry->updated_at,
        ])->values();
    }

    private function exportNews()
    {
        return News::with(['author', 'publisher', 'images', 'players', 'teams', 'tournaments'])
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->get()
            ->map(fn (News $news) => [
                'id' => $news->id,
                'lang' => $news->lang,
                'title' => $news->title,
                'slug' => $news->slug,
                'excerpt' => $news->excerpt,
                'content' => $news->content,
                'image_cover' => $news->image_cover,
                'show_on_home' => $news->show_on_home,
                'is_featured' => $news->is_featured,
                'published_at' => $news->published_at,
                'author' => $news->author ? [
                    'name' => $news->author->name,
                    'slug' => $news->author->slug,
                    'bio' => $news->author->bio,
                    'logo' => $news->author->logo,
                    'socials' => $news->author->socials,
                ] : null,
                'publisher' => $news->publisher ? [
                    'name' => $news->publisher->name,
                    'slug' => $news->publisher->slug,
                    'logo' => $news->publisher->logo,
                    'socials' => $news->publisher->socials,
                ] : null,
                'images' => $news->images->map(fn (NewsImage $image) => [
                    'id' => $image->id,
                    'url' => $image->url,
                ])->values(),
                'players' => $news->players->map(fn ($p) => ['id' => $p->id, 'handle' => $p->handle])->values(),
                'teams' => $news->teams->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->values(),
                'tournaments' => $news->tournaments->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->values(),
                'updated_at' => $news->updated_at,
            ])
            ->values();
    }
}
