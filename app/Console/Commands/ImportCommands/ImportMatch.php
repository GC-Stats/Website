<?php

/**
 * GC-Stats — Import a single match from the HenrikDev API
 *
 * Artisan command that imports one match (score, maps, player stats) from
 * the public HenrikDev API into an existing tournament phase.
 * Unlike `import:tournament`, which pulls a whole event automatically, this
 * command prompts the user for the tournament, phase, team A/B and ordering
 * info since a single match isn't tied to an event listing.
 * Usage: php artisan import:matches {match_id}
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Console\Commands\ImportCommands;

use App\Models\GameMap;
use App\Models\GamePlayerStat;
use App\Models\Matchs;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentPhase;
use App\Support\BestOfCalculator;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImportMatch extends Command
{
    protected $signature = 'import:matches {match_id : VLR Match ID}';

    protected $description = 'Import a single match (maps + player stats) from HenrikDev API';

    private string $baseUrl = 'https://api.henrikdev.xyz/valorant/v2/esports/vlr';

    public function handle(): int
    {
        $matchId = (int) $this->argument('match_id');
        $this->info("Importing match: {$matchId}");

        try {
            $detail = $this->fetchMatch($matchId);

            if (empty($detail)) {
                $this->error('No data found for this match.');

                return self::FAILURE;
            }

            $tournament = Tournament::findOrFail((int) $this->ask('Tournament ID'));
            $phase = TournamentPhase::where('tournament_id', $tournament->id)
                ->findOrFail((int) $this->ask('Phase ID'));

            $teamA = Team::findOrFail((int) $this->ask('Team A ID'));
            $teamB = Team::findOrFail((int) $this->ask('Team B ID'));

            $matchOrder = (int) $this->ask('Match order');
            $roundNumber = (int) $this->ask('Round number');
            $roundName = $this->ask('Round name (ex: Grand Final)', '');

            DB::transaction(function () use ($detail, $tournament, $phase, $teamA, $teamB, $matchOrder, $roundNumber, $roundName) {
                $tournament->teams()->syncWithoutDetaching([$teamA->id, $teamB->id]);

                $match = $this->upsertMatch($detail, $tournament, $phase, $teamA, $teamB, $matchOrder, $roundNumber, $roundName);
                $this->info('  Match: '.$teamA->name.' vs '.$teamB->name." — ID {$match->id}");

                $this->importMatchDetail($detail, $match, $teamA, $teamB);
            });

            $this->info('✅ Import complete.');

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error("❌ Error: {$e->getMessage()} at line {$e->getLine()}");

            return self::FAILURE;
        }
    }

    private function fetchMatch(int $matchId): array
    {
        $response = Http::timeout(15)->withHeaders([
            'Authorization' => config('services.henrikdev.key'),
            'Accept' => 'application/json',
        ])->get("{$this->baseUrl}/matches/{$matchId}");

        if ($response->failed()) {
            throw new \RuntimeException("API error for match {$matchId}: {$response->status()}");
        }

        return $response->json('data') ?? [];
    }

    private function upsertMatch(
        array $detail,
        Tournament $tournament,
        TournamentPhase $phase,
        Team $teamA,
        Team $teamB,
        int $matchOrder,
        int $roundNumber,
        string $roundName
    ): Matchs {
        $games = $detail['games'] ?? [];

        [$scoreA, $scoreB] = $this->countGameWins($games, $teamA, $teamB);

        $date = isset($detail['metadata']['match_time'])
            ? Carbon::parse($detail['metadata']['match_time'])
            : now();

        $isFinished = $scoreA > 0 || $scoreB > 0;

        return Matchs::updateOrCreate(
            [
                'tournament_id' => $tournament->id,
                'phase_id' => $phase->id,
                'team_a_id' => $teamA->id,
                'team_b_id' => $teamB->id,
                'match_order' => $matchOrder,
            ],
            [
                'scheduled_at' => $date,
                'status' => $isFinished ? 'finished' : 'upcoming',
                'team_a_score' => $scoreA,
                'team_b_score' => $scoreB,
                'round_number' => $roundNumber,
                'round_name' => $roundName,
            ]
        );
    }

    private function countGameWins(array $games, Team $teamA, Team $teamB): array
    {
        $winsA = 0;
        $winsB = 0;

        foreach ($games as $game) {
            $gameTeams = $game['teams'] ?? [];
            if (count($gameTeams) < 2) {
                continue;
            }

            $gameTeamA = collect($gameTeams)->firstWhere('name', $teamA->name) ?? $gameTeams[0];
            $gameTeamB = collect($gameTeams)->firstWhere('name', $teamB->name) ?? $gameTeams[1];

            if (($gameTeamA['score'] ?? 0) > ($gameTeamB['score'] ?? 0)) {
                $winsA++;
            } else {
                $winsB++;
            }
        }

        return [$winsA, $winsB];
    }

    private function importMatchDetail(array $detail, Matchs $match, Team $teamA, Team $teamB): void
    {
        $games = $detail['games'] ?? [];
        $totalMapsPlayed = count($games);

        $winsA = 0;
        $winsB = 0;
        $bestof = 1;

        foreach ($games as $mapOrder => $game) {
            $mapName = $game['map'] ?? 'Unknown';
            $gameTeams = $game['teams'] ?? [];
            $processedPlayers = [];

            if (count($gameTeams) < 2) {
                continue;
            }

            if ($gameTeams[0]['score'] > $gameTeams[1]['score']) {
                $winsA++;
            } else {
                $winsB++;
            }
            $maxWins = max($winsA, $winsB);
            $bestof = BestOfCalculator::fromMapsPlayed($totalMapsPlayed, $maxWins);

            $match->update(['best_of' => $bestof]);

            $gameTeamA = collect($gameTeams)->firstWhere('name', $teamA->name) ?? $gameTeams[0];
            $gameTeamB = collect($gameTeams)->firstWhere('name', $teamB->name) ?? $gameTeams[1];

            $gameMap = GameMap::updateOrCreate(
                ['match_id' => $match->id, 'order' => $mapOrder + 1],
                [
                    'map_name' => $mapName,
                    'team_a_score' => $gameTeamA['score'] ?? 0,
                    'team_b_score' => $gameTeamB['score'] ?? 0,
                    'is_completed' => true,
                ]
            );

            $this->line("    Map: {$mapName} ({$gameTeamA['score']}-{$gameTeamB['score']})");

            foreach ($gameTeams as $gameTeam) {
                $team = $gameTeam['name'] === $teamA->name ? $teamA : $teamB;

                foreach ($gameTeam['players'] ?? [] as $playerData) {
                    $player = $this->resolvePlayer($playerData['player'] ?? []);

                    if (! $player || in_array($player->id, $processedPlayers)) {
                        $reason = $playerData['player']['name'] ?? 'Unknown';
                        $this->warn("      Player skipped (already processed or null): {$reason}");
                        Log::warning('import:matches — player skipped (duplicate or unresolved)', [
                            'match_id' => $match->id,
                            'game_map_id' => $gameMap->id,
                            'player_name' => $reason,
                        ]);

                        continue;
                    }

                    $processedPlayers[] = $player->id;
                    $stats = $playerData['stats'] ?? [];

                    GamePlayerStat::updateOrCreate(
                        [
                            'match_id' => $match->id,
                            'game_map_id' => $gameMap->id,
                            'team_id' => $team->id,
                            'player_id' => $player?->id,
                        ],
                        [
                            'agent_name' => $playerData['agent'] ?? 'Unknown',
                            'kills' => $stats['kills'] ?? 0,
                            'deaths' => $stats['deaths'] ?? 0,
                            'assists' => $stats['assists'] ?? 0,
                            'acs' => $stats['acs'] ?? 0,
                            'adr' => $stats['adr'] ?? 0,
                            'kast_percentage' => ($stats['kast'] ?? 0) * 100,
                            'first_kills' => $stats['first_kills'] ?? 0,
                            'first_deaths' => $stats['first_deaths'] ?? 0,
                            'headshot_percentage' => ($stats['hs_pct'] ?? 0) * 100,
                        ]
                    );

                    $this->line("      Player: {$playerData['player']['name']} — {$stats['kills']}k/{$stats['deaths']}d");
                }
            }
        }
    }

    private function resolvePlayer(array $playerData): ?Player
    {
        if (empty($playerData['name'])) {
            return null;
        }

        return Player::updateOrCreate(
            ['vlr_id' => $playerData['id']],
            ['handle' => $playerData['name']]
        );
    }
}
