<?php

/**
 * GC-Stats — Import a tournament from the HenrikDev API
 *
 * Artisan command that imports a tournament along with its phases, teams,
 * matches and maps from the public HenrikDev API (VLR esports). Prompts the
 * user for the tournament's region.
 * Usage: php artisan import:tournament {event_id}
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Console\Commands;

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

class ImportTournament extends Command
{
    protected $signature = 'import:tournament {event_id : VLR Event ID}';

    protected $description = 'Import a tournament from HenrikDev API';

    private string $baseUrl = 'https://api.henrikdev.xyz/valorant/v2/esports/vlr';

    /** @var array<int, array> In-memory cache of already-fetched match details, keyed by match id. */
    private array $matchDetailCache = [];

    public function handle(): int
    {
        $eventId = $this->argument('event_id');
        $this->info("Importing event: {$eventId}");

        $region = $this->ask('Region (ex: EMEA, NA, APAC)');
        $category = $this->choice('Category', ['Cash Cups', 'Regional', 'Qualifier', 'International', 'Championship', 'Other'], 0);

        try {
            $matches = $this->fetchEventMatches($eventId);

            if (empty($matches)) {
                $this->error('No matches found for this event.');

                return self::FAILURE;
            }

            DB::transaction(function () use ($eventId, $matches, $region, $category) {
                $tournament = $this->upsertTournament($eventId, $matches, $region, $category);
                $this->info("  Tournament: {$tournament->name} (ID {$tournament->id})");

                $this->info("→ Importing {$this->countMatches($matches)} matches...");
                $this->importMatches($matches, $tournament);
            });

            $this->info('✅ Import complete.');

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error("❌ Error: {$e->getMessage()} at line {$e->getLine()}");

            return self::FAILURE;
        }
    }

    private function fetchEventMatches(string $eventId): array
    {
        $response = Http::timeout(15)->withHeaders([
            'Authorization' => config('services.henrikdev.key'),
            'Accept' => 'application/json',
        ])->get("{$this->baseUrl}/events/{$eventId}/matches");

        if ($response->failed()) {
            throw new \RuntimeException("API error: {$response->status()}");
        }

        return $response->json('data') ?? [];
    }

    private function fetchMatch(int $matchId): array
    {
        sleep(1);

        $response = Http::timeout(15)->withHeaders([
            'Authorization' => config('services.henrikdev.key'),
            'Accept' => 'application/json',
        ])->get("{$this->baseUrl}/matches/{$matchId}");

        if ($response->failed()) {
            throw new \RuntimeException("API error for match {$matchId}: {$response->status()}");
        }

        return $response->json('data') ?? [];
    }

    private function fetchMatchCached(int $matchId): array
    {
        return $this->matchDetailCache[$matchId] ??= $this->fetchMatch($matchId);
    }

    private function upsertTournament(string $eventId, array $matches, string $region, string $category): Tournament
    {
        $tournamentName = null;
        $tournamentName ??= "Event {$eventId}";

        foreach ($matches as $matchData) {
            $teams = $matchData['teams'] ?? [];
            $detail = $this->fetchMatchCached($matchData['id']);

            $this->upsertTeam([
                'id' => $detail['teams'][0]['id'] ?? null,
                'name' => $detail['teams'][0]['name'] ?? null,
            ]);

            $this->upsertTeam([
                'id' => $detail['teams'][1]['id'] ?? null,
                'name' => $detail['teams'][1]['name'] ?? null,
            ]);

            $tournamentName = $detail['metadata']['event']['title'] ?? null;
        }

        $dates = $this->extractDates($matches);

        return Tournament::updateOrCreate(
            [
                'name' => $tournamentName,
                'region' => $region,
                'category' => $category,
                'start_date' => $dates['start'],
                'end_date' => $dates['end'],
                'status' => $this->getStatus($dates['start'], $dates['end']),
            ]
        );
    }

    private function extractDates(array $matches): array
    {
        $dates = collect($matches)
            ->pluck('date')
            ->filter()
            ->map(fn ($d) => Carbon::parse($d))
            ->sort();

        return [
            'start' => $dates->first()?->toDateString() ?? now()->toDateString(),
            'end' => $dates->last()?->toDateString() ?? now()->toDateString(),
        ];
    }

    private function upsertPhase(string $eventName, Tournament $tournament): TournamentPhase
    {
        return TournamentPhase::firstOrCreate(
            [
                'tournament_id' => $tournament->id,
                'name' => $eventName,
            ],
            [
                'format' => 'bracket',
                'order' => 1,
            ]
        );
    }

    private function importMatches(array $matches, Tournament $tournament): void
    {
        $currentRoundNumber = 1;
        $lastSeenRoundName = null;
        $phasesCache = [];

        foreach ($matches as $index => $matchData) {
            $teams = $matchData['teams'] ?? [];
            if (count($teams) < 2) {
                continue;
            }

            $teamA = $this->getTeam($teams[0]);
            $teamB = $this->getTeam($teams[1]);

            $eventName = $matchData['event'] ?? 'Main Stage';

            if (! isset($phasesCache[$eventName])) {
                $phasesCache[$eventName] = $this->upsertPhase($eventName, $tournament);
            }
            $currentPhase = $phasesCache[$eventName];

            $roundName = $matchData['series'] ?? '';
            if ($roundName !== $lastSeenRoundName) {
                $currentRoundNumber++;
                $lastSeenRoundName = $roundName;
            }

            if ($teamA) {
                $tournament->teams()->syncWithoutDetaching([$teamA->id]);
            }

            if ($teamB) {
                $tournament->teams()->syncWithoutDetaching([$teamB->id]);
            }

            $scoreA = $teams[0]['score'] ?? 0;
            $scoreB = $teams[1]['score'] ?? 0;
            $date = $matchData['date'] ? Carbon::parse($matchData['date']) : now();
            $isFinished = $scoreA > 0 || $scoreB > 0;

            $match = Matchs::updateOrCreate(
                [
                    'tournament_id' => $tournament->id,
                    'phase_id' => $currentPhase->id,
                    'team_a_id' => $teamA->id ?? null,
                    'team_b_id' => $teamB->id ?? null,
                    'scheduled_at' => $date,
                    'status' => $isFinished ? 'finished' : 'upcoming',
                    'team_a_score' => $scoreA,
                    'team_b_score' => $scoreB,
                    'round_number' => $currentRoundNumber,
                    'match_order' => $index + 1,
                    'round_name' => $matchData['series'] ?? '',
                ]
            );

            $this->line('  Match: '.($teamA->name ?? 'Unknown').' vs '.($teamB->name ?? 'Unknown')." ({$scoreA}-{$scoreB}) — ID {$match->id}");

            if ($isFinished) {
                sleep(1);
                $this->importMatchDetail($matchData['id'], $match, $teamA, $teamB);
            }
        }
    }

    private function importMatchDetail(int $matchId, Matchs $match, Team $teamA, Team $teamB): void
    {
        try {
            $detail = $this->fetchMatchCached($matchId);
        } catch (\Throwable $e) {
            $this->warn("    Match detail fetch failed: {$e->getMessage()}");

            return;
        }

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

            $gTeams = $game['teams'] ?? [];
            if (count($gTeams) < 2) {
                continue;
            }

            if ($gTeams[0]['score'] > $gTeams[1]['score']) {
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

                    // Temporary fix: Henrikdev is actually giving the team a & b player in team b
                    if (! $player || in_array($player->id, $processedPlayers)) {
                        $reason = $playerData['player']['name'] ?? 'Unknown';
                        $this->warn("      Player skipped (already processed or null): {$reason}");
                        Log::warning('import:tournament — player skipped (duplicate or unresolved)', [
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

    private function upsertTeam(array $teamData): ?Team
    {
        if (! $teamData['name']) {
            return null;
        }

        return Team::updateOrCreate(
            ['vlr_id' => $teamData['id']],
            ['name' => $teamData['name']]
        );
    }

    private function getTeam(array $teamData): ?Team
    {
        return Team::where('name', $teamData['name'])->first();
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

    private function getStatus(?string $start, ?string $end): string
    {
        if (! $start || ! $end) {
            return 'upcoming';
        }
        $now = now();
        if ($now->lt(Carbon::parse($start))) {
            return 'upcoming';
        }
        if ($now->gt(Carbon::parse($end))) {
            return 'finished';
        }

        return 'live';
    }

    private function countMatches(array $matches): int
    {
        return count($matches);
    }
}
