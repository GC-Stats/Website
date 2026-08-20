<?php

/**
 * GC-Stats — Kill-event stats aggregator
 *
 * Derives everything the stats tables' "extra" columns need from
 * game_map_round_kills (raw Riot match data — damage_type is one of Weapon,
 * Ability, Bomb, Melee, Fall; weapon holds either the gun/signature-weapon
 * name for damage_type=Weapon, or the kill-feed ability slot — Ability1,
 * Ability2, GrenadeAbility, Ultimate — for damage_type=Ability):
 * ability/ultimate kills, fall deaths, per-weapon kills, and multi-kill
 * counts (2K/3K/4K/5K, i.e. rounds where a player got exactly N kills).
 * Every figure is returned both as a total (sum) and as an average per map
 * (divided by the caller's games_played, same denominator the main stats
 * query already computed), so the table's total/average toggle has both
 * without a second round of queries.
 *
 * Kills are joined through game_map_rounds to game_player_stats to resolve
 * which agent the killer was playing on that map, so results can be
 * grouped either per player (tournament stats table, agent-agnostic column
 * labels) or per agent (player stats table, which already breaks a player
 * down per agent). Fall deaths are the one exception — a fall death has no
 * meaningful "killer", so that query joins on victim_player_id instead, to
 * resolve the agent the *victim* was playing. Optional
 * $agentNames/$mapNames/$roleSlugs narrow the underlying kills to a
 * specific agent/role/map selection, matching the same filters applied to
 * the main GamePlayerStat stats query, so the "extra" columns stay
 * consistent with the filtered rows.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UtilityStatsAggregator
{
    /**
     * One row per player_id with total_/avg_ pairs for ability1_kills,
     * ability2_kills, grenade_kills, ultimate_kills, fall_deaths,
     * multi_2k/3k/4k/5k, and weapon_<slug> for every weapon in $weapons.
     *
     * @param  array<int, int>  $gamesPlayed
     * @param  list<string>  $weapons
     * @param  list<string>  $agentNames
     * @param  list<string>  $mapNames
     * @param  list<string>  $roleSlugs
     * @return Collection<int, array>
     */
    public function perPlayer(int $tournamentId, ?Collection $phaseIds, ?CarbonInterface $start, ?CarbonInterface $end, array $gamesPlayed, array $weapons, array $agentNames = [], array $mapNames = [], array $roleSlugs = []): Collection
    {
        $kills = $this->killTotalsQuery($tournamentId, $phaseIds, $start, $end, null, $agentNames, $mapNames, $roleSlugs)
            ->addSelect('game_map_round_kills.killer_player_id as key')
            ->groupBy('game_map_round_kills.killer_player_id')
            ->get()
            ->keyBy('key');

        $multiKills = $this->multiKillCountsQuery($tournamentId, $phaseIds, $start, $end, null, 'killer_player_id', $agentNames, $mapNames, $roleSlugs)
            ->get()
            ->keyBy('key');

        $weaponKills = $this->weaponKillCountsQuery($tournamentId, $phaseIds, $start, $end, null, $agentNames, $mapNames, $roleSlugs)
            ->addSelect('game_map_round_kills.killer_player_id as key')
            ->groupBy('game_map_round_kills.killer_player_id', 'game_map_round_kills.weapon')
            ->get()
            ->groupBy('key');

        $fallDeaths = $this->fallDeathsQuery($tournamentId, $phaseIds, $start, $end, null, $agentNames, $mapNames, $roleSlugs)
            ->addSelect('game_map_round_kills.victim_player_id as key')
            ->groupBy('game_map_round_kills.victim_player_id')
            ->get()
            ->keyBy('key');

        return collect($gamesPlayed)->mapWithKeys(function ($games, $playerId) use ($kills, $multiKills, $weaponKills, $fallDeaths, $weapons) {
            return [$playerId => $this->composeRow((int) $games, $kills->get($playerId), $multiKills->get($playerId), $weaponKills->get($playerId, collect()), $fallDeaths->get($playerId), $weapons)];
        });
    }

    /**
     * One row per agent_name for a single player, same shape as perPlayer().
     *
     * @param  array<string, int>  $gamesPlayedByAgent
     * @param  list<string>  $weapons
     * @param  list<string>  $mapNames
     * @param  list<string>  $roleSlugs
     * @return Collection<string, array>
     */
    public function perAgent(int $playerId, ?CarbonInterface $start, ?CarbonInterface $end, array $gamesPlayedByAgent, array $weapons, array $mapNames = [], array $roleSlugs = []): Collection
    {
        $kills = $this->killTotalsQuery(null, null, $start, $end, $playerId, [], $mapNames, $roleSlugs)
            ->addSelect('game_player_stats.agent_name as key')
            ->groupBy('game_player_stats.agent_name')
            ->get()
            ->keyBy('key');

        $multiKills = $this->multiKillCountsQuery(null, null, $start, $end, $playerId, 'agent_name', [], $mapNames, $roleSlugs)
            ->get()
            ->keyBy('key');

        $weaponKills = $this->weaponKillCountsQuery(null, null, $start, $end, $playerId, [], $mapNames, $roleSlugs)
            ->addSelect('game_player_stats.agent_name as key')
            ->groupBy('game_player_stats.agent_name', 'game_map_round_kills.weapon')
            ->get()
            ->groupBy('key');

        $fallDeaths = $this->fallDeathsQuery(null, null, $start, $end, $playerId, [], $mapNames, $roleSlugs)
            ->addSelect('game_player_stats.agent_name as key')
            ->groupBy('game_player_stats.agent_name')
            ->get()
            ->keyBy('key');

        return collect($gamesPlayedByAgent)->mapWithKeys(function ($games, $agentName) use ($kills, $multiKills, $weaponKills, $fallDeaths, $weapons) {
            return [$agentName => $this->composeRow((int) $games, $kills->get($agentName), $multiKills->get($agentName), $weaponKills->get($agentName, collect()), $fallDeaths->get($agentName), $weapons)];
        });
    }

    /**
     * Distinct gun/signature-weapon names (damage_type = Weapon) seen in
     * scope, alphabetically sorted, for building the dynamic weapon-kill
     * columns.
     *
     * @param  list<string>  $agentNames
     * @param  list<string>  $mapNames
     * @param  list<string>  $roleSlugs
     * @return list<string>
     */
    public function weaponsFor(?int $tournamentId, ?Collection $phaseIds, ?CarbonInterface $start, ?CarbonInterface $end, ?int $playerId = null, array $agentNames = [], array $mapNames = [], array $roleSlugs = []): array
    {
        $weapons = $this->scopedKillsQuery($tournamentId, $phaseIds, $start, $end, $playerId, $agentNames, $mapNames, $roleSlugs)
            ->where('game_map_round_kills.damage_type', 'Weapon')
            ->whereNotNull('game_map_round_kills.weapon')
            ->distinct()
            ->pluck('game_map_round_kills.weapon')
            ->all();

        sort($weapons, SORT_STRING | SORT_FLAG_CASE);

        return $weapons;
    }

    public static function weaponKey(string $weapon): string
    {
        return 'weapon_'.Str::slug($weapon, '_');
    }

    private function composeRow(int $games, ?object $killRow, ?object $multiRow, Collection $weaponRows, ?object $fallDeathRow, array $weapons): array
    {
        $divide = fn ($total) => $games > 0 ? round($total / $games, 2) : 0.0;

        $row = [
            'ability1_kills' => $killRow->ability1_kills ?? 0,
            'ability2_kills' => $killRow->ability2_kills ?? 0,
            'grenade_kills' => $killRow->grenade_kills ?? 0,
            'ultimate_kills' => $killRow->ultimate_kills ?? 0,
            'fall_deaths' => $fallDeathRow->fall_deaths ?? 0,
            'multi_2k' => $multiRow->multi_2k ?? 0,
            'multi_3k' => $multiRow->multi_3k ?? 0,
            'multi_4k' => $multiRow->multi_4k ?? 0,
            'multi_5k' => $multiRow->multi_5k ?? 0,
        ];

        $weaponCounts = $weaponRows->pluck('kills', 'weapon');

        foreach ($weapons as $weapon) {
            $row[self::weaponKey($weapon)] = (int) ($weaponCounts[$weapon] ?? 0);
        }

        $result = [];
        foreach ($row as $key => $total) {
            $result["total_{$key}"] = (int) $total;
            $result["avg_{$key}"] = $divide($total);
        }

        return $result;
    }

    /**
     * @param  list<string>  $agentNames
     * @param  list<string>  $mapNames
     * @param  list<string>  $roleSlugs
     */
    private function scopedKillsQuery(?int $tournamentId, ?Collection $phaseIds, ?CarbonInterface $start, ?CarbonInterface $end, ?int $playerId = null, array $agentNames = [], array $mapNames = [], array $roleSlugs = [])
    {
        return DB::table('game_map_round_kills')
            ->join('game_map_rounds', 'game_map_rounds.id', '=', 'game_map_round_kills.game_map_round_id')
            ->join('game_player_stats', function ($join) {
                $join->on('game_player_stats.game_map_id', '=', 'game_map_rounds.game_map_id')
                    ->on('game_player_stats.player_id', '=', 'game_map_round_kills.killer_player_id');
            })
            ->when($mapNames !== [], fn ($q) => $q->join('game_maps', 'game_maps.id', '=', 'game_map_rounds.game_map_id'))
            ->whereNotNull('game_map_round_kills.killer_player_id')
            ->when($tournamentId !== null, fn ($q) => $q->where('game_map_round_kills.tournament_id', $tournamentId))
            ->when($phaseIds !== null, fn ($q) => $q->whereIn('game_map_round_kills.phase_id', $phaseIds))
            ->when($playerId !== null, fn ($q) => $q->where('game_map_round_kills.killer_player_id', $playerId))
            ->when($agentNames !== [], fn ($q) => $q->whereIn('game_player_stats.agent_name', $agentNames))
            ->when($mapNames !== [], fn ($q) => $q->whereIn('game_maps.map_name', $mapNames))
            ->when($roleSlugs !== [], fn ($q) => $q->whereIn(DB::raw('LOWER(REPLACE(game_player_stats.agent_name, "/", ""))'), $roleSlugs))
            ->when($start && $end, fn ($q) => $q->whereBetween('game_map_round_kills.created_at', [$start, $end]));
    }

    private function killTotalsQuery(?int $tournamentId, ?Collection $phaseIds, ?CarbonInterface $start, ?CarbonInterface $end, ?int $playerId = null, array $agentNames = [], array $mapNames = [], array $roleSlugs = [])
    {
        return $this->scopedKillsQuery($tournamentId, $phaseIds, $start, $end, $playerId, $agentNames, $mapNames, $roleSlugs)
            ->selectRaw("
                SUM(CASE WHEN game_map_round_kills.damage_type = 'Ability' AND game_map_round_kills.weapon = 'Ability1' THEN 1 ELSE 0 END) as ability1_kills,
                SUM(CASE WHEN game_map_round_kills.damage_type = 'Ability' AND game_map_round_kills.weapon = 'Ability2' THEN 1 ELSE 0 END) as ability2_kills,
                SUM(CASE WHEN game_map_round_kills.damage_type = 'Ability' AND game_map_round_kills.weapon = 'GrenadeAbility' THEN 1 ELSE 0 END) as grenade_kills,
                SUM(CASE WHEN game_map_round_kills.damage_type = 'Ability' AND game_map_round_kills.weapon = 'Ultimate' THEN 1 ELSE 0 END) as ultimate_kills
            ");
    }

    /**
     * Fall deaths joined on victim_player_id instead of killer_player_id —
     * a death by fall damage has no meaningful "killer" to attribute it to,
     * so this resolves the agent the *victim* was playing instead.
     *
     * @param  list<string>  $agentNames
     * @param  list<string>  $mapNames
     * @param  list<string>  $roleSlugs
     */
    private function fallDeathsQuery(?int $tournamentId, ?Collection $phaseIds, ?CarbonInterface $start, ?CarbonInterface $end, ?int $playerId = null, array $agentNames = [], array $mapNames = [], array $roleSlugs = [])
    {
        return $this->scopedDeathsQuery($tournamentId, $phaseIds, $start, $end, $playerId, $agentNames, $mapNames, $roleSlugs)
            ->where('game_map_round_kills.damage_type', 'Fall')
            ->selectRaw('COUNT(*) as fall_deaths');
    }

    /**
     * @param  list<string>  $agentNames
     * @param  list<string>  $mapNames
     * @param  list<string>  $roleSlugs
     */
    private function scopedDeathsQuery(?int $tournamentId, ?Collection $phaseIds, ?CarbonInterface $start, ?CarbonInterface $end, ?int $playerId = null, array $agentNames = [], array $mapNames = [], array $roleSlugs = [])
    {
        return DB::table('game_map_round_kills')
            ->join('game_map_rounds', 'game_map_rounds.id', '=', 'game_map_round_kills.game_map_round_id')
            ->join('game_player_stats', function ($join) {
                $join->on('game_player_stats.game_map_id', '=', 'game_map_rounds.game_map_id')
                    ->on('game_player_stats.player_id', '=', 'game_map_round_kills.victim_player_id');
            })
            ->when($mapNames !== [], fn ($q) => $q->join('game_maps', 'game_maps.id', '=', 'game_map_rounds.game_map_id'))
            ->whereNotNull('game_map_round_kills.victim_player_id')
            ->when($tournamentId !== null, fn ($q) => $q->where('game_map_round_kills.tournament_id', $tournamentId))
            ->when($phaseIds !== null, fn ($q) => $q->whereIn('game_map_round_kills.phase_id', $phaseIds))
            ->when($playerId !== null, fn ($q) => $q->where('game_map_round_kills.victim_player_id', $playerId))
            ->when($agentNames !== [], fn ($q) => $q->whereIn('game_player_stats.agent_name', $agentNames))
            ->when($mapNames !== [], fn ($q) => $q->whereIn('game_maps.map_name', $mapNames))
            ->when($roleSlugs !== [], fn ($q) => $q->whereIn(DB::raw('LOWER(REPLACE(game_player_stats.agent_name, "/", ""))'), $roleSlugs))
            ->when($start && $end, fn ($q) => $q->whereBetween('game_map_round_kills.created_at', [$start, $end]));
    }

    /**
     * Kills-in-round per (round, killer/agent), wrapped so the outer query
     * can bucket rounds into 2K/3K/4K/5K+ tiers. $groupCol is
     * 'killer_player_id' (perPlayer) or 'agent_name' (perAgent) — the
     * subquery always selects both, the outer query groups/keys by
     * whichever one the caller needs.
     */
    private function multiKillCountsQuery(?int $tournamentId, ?Collection $phaseIds, ?CarbonInterface $start, ?CarbonInterface $end, ?int $playerId, string $groupCol, array $agentNames = [], array $mapNames = [], array $roleSlugs = [])
    {
        $roundKillCounts = $this->scopedKillsQuery($tournamentId, $phaseIds, $start, $end, $playerId, $agentNames, $mapNames, $roleSlugs)
            ->select('game_map_round_kills.killer_player_id', 'game_player_stats.agent_name', 'game_map_round_kills.game_map_round_id')
            ->selectRaw('COUNT(*) as kills_in_round')
            ->groupBy('game_map_round_kills.killer_player_id', 'game_player_stats.agent_name', 'game_map_round_kills.game_map_round_id');

        return DB::query()->fromSub($roundKillCounts, 'round_kills')
            ->selectRaw("round_kills.{$groupCol} as `key`")
            ->selectRaw('
                SUM(CASE WHEN round_kills.kills_in_round = 2 THEN 1 ELSE 0 END) as multi_2k,
                SUM(CASE WHEN round_kills.kills_in_round = 3 THEN 1 ELSE 0 END) as multi_3k,
                SUM(CASE WHEN round_kills.kills_in_round = 4 THEN 1 ELSE 0 END) as multi_4k,
                SUM(CASE WHEN round_kills.kills_in_round >= 5 THEN 1 ELSE 0 END) as multi_5k
            ')
            ->groupBy("round_kills.{$groupCol}");
    }

    private function weaponKillCountsQuery(?int $tournamentId, ?Collection $phaseIds, ?CarbonInterface $start, ?CarbonInterface $end, ?int $playerId = null, array $agentNames = [], array $mapNames = [], array $roleSlugs = [])
    {
        return $this->scopedKillsQuery($tournamentId, $phaseIds, $start, $end, $playerId, $agentNames, $mapNames, $roleSlugs)
            ->where('game_map_round_kills.damage_type', 'Weapon')
            ->whereNotNull('game_map_round_kills.weapon')
            ->select('game_map_round_kills.weapon')
            ->selectRaw('COUNT(*) as kills');
    }
}
