<?php

/**
 * GC-Stats — Match page controller
 *
 * Renders the match detail page (teams, score, maps, vetos, stats) with a
 * cache TTL that varies depending on the match status (live/upcoming/finished).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers;

use App\Helpers\CacheTtl;
use App\Models\Matchs;
use App\Models\User;
use App\Support\PublisherScope;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MatchController extends Controller
{
    public function index($id)
    {
        $cacheKey = "match_{$id}";
        $tag = "match_{$id}";

        $meta = DB::table('matches')
            ->join('tournaments', 'tournaments.id', '=', 'matches.tournament_id')
            ->where('matches.id', $id)
            ->select('matches.status', 'tournaments.active as tournament_active')
            ->first();

        abort_unless($meta, 404);

        if (! $meta->tournament_active) {
            abort_unless(auth()->user()?->can('tournaments.view'), 404);
        }

        if ($meta->tournament_active) {
            $cached = Cache::tags([$tag])->get($cacheKey);
            if ($cached) {
                return $this->respondWithMatchView($cached, $cached['match']['status']);
            }
        }

        $status = $meta->status;

        $buildMatchData = function () use ($id) {
            $match = Matchs::with([
                'tournament:id,name',
                'tournamentPhase:id,name',
                'teamA:id,name,short_name',
                'teamB:id,name,short_name',
                'map_bans.team:id,name,short_name',
                'map_bans.sidePickedBy:id,name,short_name',
                'streams' => fn ($query) => $query->active()->orderBy('name'),
                'vods.gameMap:id,map_name',
            ])->findOrFail($id);

            $allPlayerStats = DB::table('game_player_stats')
                ->join('players', 'game_player_stats.player_id', '=', 'players.id')
                ->where('game_player_stats.match_id', $id)
                ->select([
                    'game_player_stats.*',
                    'players.handle as player_handle',
                ])
                ->get();

            $idsA = [];
            $idsB = [];

            foreach ($allPlayerStats->groupBy('player_id') as $playerId => $rows) {
                $majorityTeamId = $rows->countBy('team_id')->sortDesc()->keys()->first();

                if ($majorityTeamId == $match->team_a_id) {
                    $idsA[] = $playerId;
                } elseif ($majorityTeamId == $match->team_b_id) {
                    $idsB[] = $playerId;
                }
            }

            $performanceData = DB::table('game_map_round_player_stats as ps')
                ->join('game_map_rounds as r', 'ps.game_map_round_id', '=', 'r.id')
                ->where('r.match_id', $id)
                ->select([
                    'r.game_map_id',
                    'ps.player_id',
                    DB::raw('SUM(CASE WHEN ps.kills = 2 THEN 1 ELSE 0 END) as json_2k'),
                    DB::raw('SUM(CASE WHEN ps.kills = 3 THEN 1 ELSE 0 END) as json_3k'),
                    DB::raw('SUM(CASE WHEN ps.kills = 4 THEN 1 ELSE 0 END) as json_4k'),
                    DB::raw('SUM(CASE WHEN ps.kills >= 5 THEN 1 ELSE 0 END) as json_5k'),
                    DB::raw("SUM(CASE WHEN ps.weapon_id = 'Sheriff' THEN ps.kills ELSE 0 END) as sheriff_kills"),
                ])
                ->groupBy('r.game_map_id', 'ps.player_id')
                ->get()
                ->groupBy('game_map_id');

            $sqlIdsA = count($idsA) ? implode(',', array_map('intval', $idsA)) : '0';
            $sqlIdsB = count($idsB) ? implode(',', array_map('intval', $idsB)) : '0';

            $roundEconomy = DB::table('game_map_round_player_stats as ps')
                ->join('game_map_rounds as r', 'ps.game_map_round_id', '=', 'r.id')
                ->where('r.match_id', $id)
                ->select([
                    'r.game_map_id',
                    'r.id as round_id',
                    'r.winning_team as winning_team_id',
                    DB::raw("SUM(CASE WHEN ps.player_id IN ({$sqlIdsA}) THEN ps.loadout_value ELSE 0 END) as spent_a"),
                    DB::raw("SUM(CASE WHEN ps.player_id IN ({$sqlIdsB}) THEN ps.loadout_value ELSE 0 END) as spent_b"),
                ])
                ->groupBy('r.game_map_id', 'r.id', 'r.winning_team')
                ->get()
                ->groupBy('game_map_id');

            $roundHistory = DB::table('game_map_rounds')
                ->where('match_id', $id)
                ->select(['game_map_id', 'round_number', 'winning_team', 'win_type'])
                ->orderBy('round_number')
                ->get()
                ->groupBy('game_map_id');

            $rawMaps = DB::table('game_maps')
                ->where('match_id', $id)
                ->orderBy('order')
                ->get();

            $ecoTiers = [
                'eco' => ['min' => 0, 'max' => 5000, 'label' => 'Eco', 'icon' => '¤'],
                'semi_eco' => ['min' => 5001, 'max' => 10000, 'label' => 'Semi-Eco', 'icon' => '$'],
                'semi_buy' => ['min' => 10001, 'max' => 20000, 'label' => 'Semi-Buy', 'icon' => '$$'],
                'full_buy' => ['min' => 20001, 'max' => 1000000, 'label' => 'Full Buy', 'icon' => '$$$'],
            ];

            $maps = [];
            foreach ($rawMaps as $map) {
                $mapId = $map->id;
                $mapArray = (array) $map;

                $mapStatsRaw = $allPlayerStats->where('game_map_id', $mapId);
                $stats = [];
                foreach ($mapStatsRaw as $s) {
                    $stats[$s->player_id] = [
                        'player_id' => $s->player_id,
                        'player' => ['id' => $s->player_id, 'handle' => $s->player_handle],
                        'agent_name' => $s->agent_name,
                        'acs' => (int) $s->acs,
                        'kills' => (int) $s->kills,
                        'deaths' => (int) $s->deaths,
                        'assists' => (int) $s->assists,
                        'adr' => (int) $s->adr,
                        'kast_percentage' => (float) $s->kast_percentage,
                        'first_kills' => (int) $s->first_kills,
                        'first_deaths' => (int) $s->first_deaths,
                        'headshot_percentage' => (float) $s->headshot_percentage,
                    ];
                }
                $mapArray['stats'] = $stats;
                $mapArray['stats_a'] = collect($stats)->whereIn('player_id', $idsA)->sortByDesc('acs')->values()->toArray();
                $mapArray['stats_b'] = collect($stats)->whereIn('player_id', $idsB)->sortByDesc('acs')->values()->toArray();

                $perfRaw = $performanceData->get($mapId) ?? collect();
                $performance = [];
                foreach ($perfRaw as $p) {
                    $performance[$p->player_id] = [
                        '2k' => (int) $p->json_2k,
                        '3k' => (int) $p->json_3k,
                        '4k' => (int) $p->json_4k,
                        '5k' => (int) $p->json_5k,
                        'sheriff_kills' => (int) $p->sheriff_kills,
                    ];
                }
                $mapArray['performance'] = $performance;

                $mapRounds = $roundEconomy->get($mapId) ?? collect();
                $ecoSummary = [];

                foreach (['team_a', 'team_b'] as $teamKey) {
                    $spentField = ($teamKey === 'team_a') ? 'spent_a' : 'spent_b';
                    $targetTeamId = ($teamKey === 'team_a') ? $match->team_a_id : $match->team_b_id;

                    $teamStats = [];
                    foreach ($ecoTiers as $tierKey => $tier) {
                        $roundsInTier = $mapRounds->filter(fn ($r) => $tier['min'] <= $r->$spentField && $tier['max'] >= $r->$spentField);

                        $teamStats[$tierKey] = [
                            'label' => $tier['label'],
                            'icon' => $tier['icon'],
                            'total' => $roundsInTier->count(),
                            'win' => $roundsInTier->where('winning_team_id', $targetTeamId)->count(),
                        ];
                    }
                    $ecoSummary[$teamKey] = $teamStats;
                }
                $mapArray['eco_summary'] = $ecoSummary;

                $mapArray['rounds'] = ($roundHistory->get($mapId) ?? collect())
                    ->map(fn ($r) => [
                        'round_number' => (int) $r->round_number,
                        'winning_team' => (int) $r->winning_team,
                        'win_type' => $r->win_type,
                    ])
                    ->values()
                    ->toArray();

                $maps[] = $mapArray;
            }

            $totalA = [];
            $totalB = [];

            foreach ($maps as $map) {
                foreach ($map['stats'] as $playerId => $s) {
                    if (in_array($playerId, $idsA)) {
                        $target = &$totalA;
                    } elseif (in_array($playerId, $idsB)) {
                        $target = &$totalB;
                    } else {
                        continue;
                    }

                    if (! isset($target[$playerId])) {
                        $target[$playerId] = $s;
                        $target[$playerId]['maps_played'] = 1;
                    } else {
                        $target[$playerId]['kills'] += $s['kills'];
                        $target[$playerId]['deaths'] += $s['deaths'];
                        $target[$playerId]['assists'] += $s['assists'];
                        $target[$playerId]['first_kills'] += $s['first_kills'];
                        $target[$playerId]['first_deaths'] += $s['first_deaths'];
                        $target[$playerId]['acs'] += $s['acs'];
                        $target[$playerId]['adr'] += $s['adr'];
                        $target[$playerId]['kast_percentage'] += $s['kast_percentage'];
                        $target[$playerId]['headshot_percentage'] += $s['headshot_percentage'];
                        $target[$playerId]['maps_played']++;
                    }
                }
            }

            foreach ($totalA as &$p) {
                $p['acs'] = (int) round($p['acs'] / $p['maps_played']);
                $p['adr'] = (int) round($p['adr'] / $p['maps_played']);
                $p['kast_percentage'] = round($p['kast_percentage'] / $p['maps_played'], 2);
                $p['headshot_percentage'] = round($p['headshot_percentage'] / $p['maps_played'], 2);
                unset($p['maps_played']);
            }
            unset($p);

            foreach ($totalB as &$p) {
                $p['acs'] = (int) round($p['acs'] / $p['maps_played']);
                $p['adr'] = (int) round($p['adr'] / $p['maps_played']);
                $p['kast_percentage'] = round($p['kast_percentage'] / $p['maps_played'], 2);
                $p['headshot_percentage'] = round($p['headshot_percentage'] / $p['maps_played'], 2);
                unset($p['maps_played']);
            }
            unset($p);

            $totalA = collect($totalA)->sortByDesc('acs')->values()->all();
            $totalB = collect($totalB)->sortByDesc('acs')->values()->all();

            $totalEcoSummary = ['team_a' => [], 'team_b' => []];
            foreach (['team_a', 'team_b'] as $teamKey) {
                foreach ($ecoTiers as $tierKey => $tier) {
                    $totalEcoSummary[$teamKey][$tierKey] = [
                        'label' => $tier['label'],
                        'icon' => $tier['icon'],
                        'total' => 0,
                        'win' => 0,
                    ];
                }
            }

            $totalPerformance = [];

            foreach ($maps as $map) {
                foreach (['team_a', 'team_b'] as $teamKey) {
                    foreach ($map['eco_summary'][$teamKey] as $tierKey => $tierData) {
                        $totalEcoSummary[$teamKey][$tierKey]['total'] += $tierData['total'];
                        $totalEcoSummary[$teamKey][$tierKey]['win'] += $tierData['win'];
                    }
                }

                foreach ($map['performance'] as $playerId => $perf) {
                    if (! isset($totalPerformance[$playerId])) {
                        $totalPerformance[$playerId] = $perf;
                    } else {
                        foreach (['2k', '3k', '4k', '5k', 'sheriff_kills'] as $k) {
                            $totalPerformance[$playerId][$k] += $perf[$k];
                        }
                    }
                }
            }

            $finalMatch = $match->toArray();
            $finalMatch['game_maps'] = $maps;

            $finalMatch['team_a_data'] = $match->teamA ? [
                ...$match->teamA->only(['id', 'name', 'short_name']),
                'logo' => $match->teamA->logo,
            ] : null;

            $finalMatch['team_b_data'] = $match->teamB ? [
                ...$match->teamB->only(['id', 'name', 'short_name']),
                'logo' => $match->teamB->logo,
            ] : null;

            return [
                'match' => $finalMatch,
                'idsA' => $idsA,
                'idsB' => $idsB,
                'totalA' => $totalA,
                'totalB' => $totalB,
                'totalEcoSummary' => $totalEcoSummary,
                'totalPerformance' => $totalPerformance,
            ];
        };

        if (! $meta->tournament_active) {
            $matchData = $buildMatchData();

            return response()
                ->view('match', array_merge($matchData, ['inactive_access' => true], $this->perRequestViewData()))
                ->header('Cache-Control', 'private, no-store')
                ->header('Vary', 'Accept-Language');
        }

        $matchData = Cache::remember($cacheKey, CacheTtl::forMatch($status), $buildMatchData);

        return $this->respondWithMatchView($matchData, $matchData['match']['status']);
    }

    /**
     * Builds the public match-page response. The per-visitor permission
     * flags (canLinkStreams/canLinkVods) must never be exposed to a shared
     * cache: a CDN that stored one visitor's permission-gated HTML would
     * replay it to every other visitor. So whenever those flags are
     * anything other than the anonymous default, the response is forced
     * private/no-store instead of the normal shared "public" caching.
     */
    private function respondWithMatchView(array $matchData, string $status)
    {
        $perRequest = $this->perRequestViewData();

        if ($perRequest['canLinkStreams'] || $perRequest['canLinkVods']) {
            return response()
                ->view('match', array_merge($matchData, $perRequest))
                ->header('Cache-Control', 'private, no-store')
                ->header('Vary', 'Accept-Language');
        }

        $ttl = match ($status) {
            'finished' => 86400 * 30,
            'upcoming' => 86400,
            'live' => 60,
            default => 3600,
        };

        return response()
            ->view('match', array_merge($matchData, $perRequest))
            ->header('Cache-Control', "public, max-age={$ttl}, s-maxage={$ttl}")
            ->header('Vary', 'Accept-Language');
    }

    /**
     * View data that must never end up inside the cached match payload
     * (see $buildMatchData/Cache::remember above) because it depends on the
     * current visitor — computed fresh on every request and merged in after
     * the cache read/write, for all three response paths.
     *
     * @return array{canLinkStreams: bool, canLinkVods: bool}
     */
    private function perRequestViewData(): array
    {
        $user = auth()->user();

        return [
            'canLinkStreams' => $this->canLinkPermission($user, 'streams.matches.link', 'publisher.streams.link'),
            'canLinkVods' => $this->canLinkPermission($user, 'vods.matches.link', 'publisher.vods.link'),
        ];
    }

    /**
     * Whether the current visitor may link a stream channel/VOD to a match
     * from the public match page — site editors with the admin permission,
     * or a publisher's own member with the matching publisher permission
     * (see Admin\MatchStreamController/Admin\MatchVodController, reused as-is:
     * they authorize per-channel/per-VOD regardless of which page the
     * request came from). Publishers have no access to the admin match
     * list/show pages (gated by matches.view), so this is their only way to
     * link their own channels/VODs.
     */
    private function canLinkPermission(?User $user, string $adminPermission, string $publisherPermission): bool
    {
        if (! $user) {
            return false;
        }

        return $user->can($adminPermission)
            || PublisherScope::publisherIdsWithPermission($user->id, $publisherPermission)->isNotEmpty();
    }
}
