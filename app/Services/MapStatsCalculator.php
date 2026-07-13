<?php

/**
 * GC-Stats — Map statistics calculator
 *
 * Pure computation of per-map, per-player statistics (KAST/ACS/ADR/HS,
 * clutches, multi-kills, trades, economy round outcomes, ATK/DEF splits)
 * from raw Riot match round data. Extracted from ApiGameMapController so
 * the trade/pistol/eco/clutch logic can be tested and reasoned about in
 * isolation from HTTP/persistence concerns.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use Illuminate\Support\Collection;

class MapStatsCalculator
{
    public const TRADE_WINDOW_MS = 3000;

    public const FIRST_HALF_ROUNDS = 12;

    // Buy-type thresholds on the team's combined (5-player) round loadout value.
    public const TEAM_ECO_MAX_LOADOUT = 5000;

    public const TEAM_FORCE_MAX_LOADOUT = 20000;

    public const PISTOL_ROUNDS = [1, 13];

    /**
     * Normalize a round's raw kill events, resolving environmental deaths
     * (bomb detonations / suicides) to a null killer so they're never
     * credited as a kill nor treated as a tradeable death.
     */
    public function extractKills(array $round): Collection
    {
        $kills = collect();

        foreach ($round['playerStats'] ?? [] as $pStat) {
            foreach ($pStat['kills'] ?? [] as $kill) {
                $finishingDamage = $kill['finishingDamage'] ?? [];
                $killer = $kill['killer'] ?? '';
                $damageType = $finishingDamage['damageType'] ?? null;
                $weapon = $finishingDamage['damageItem'] ?? null;

                $isEnvironmental = $killer === '' || $killer === $kill['victim'] || $damageType === 'Bomb';

                $kills->push([
                    'killer' => $isEnvironmental ? null : $killer,
                    'victim' => $kill['victim'],
                    'time' => $kill['timeSinceRoundStartMillis'],
                    'assistants' => $isEnvironmental ? [] : ($kill['assistants'] ?? []),
                    'weapon' => $isEnvironmental ? null : $weapon,
                    'damage_type' => $damageType,
                    'is_secondary_fire' => $finishingDamage['isSecondaryFireMode'] ?? false,
                ]);
            }
        }

        return $kills->sortBy('time')->values();
    }

    /**
     * Find which team's color attacked first, using the earliest first-half
     * round with a bomb plant (defenders never plant).
     */
    public function firstHalfAttackerColor(Collection $rounds, Collection $players): ?string
    {
        $round = $rounds
            ->filter(fn ($r) => $r['roundNum'] < self::FIRST_HALF_ROUNDS && ! empty($r['bombPlanter']))
            ->sortBy('roundNum')
            ->first();

        if (! $round) {
            return null;
        }

        return optional($players->firstWhere('puuid', $round['bombPlanter']))['teamId'];
    }

    /**
     * Determine which team color is on attack for a given 0-indexed round,
     * from the known first-half attacker, swapping at halftime and
     * alternating every round in overtime.
     */
    public function attackerColorForRoundIndex(int $index, ?string $firstHalfAttackerColor, ?string $teamAColor, ?string $teamBColor): ?string
    {
        if (! $firstHalfAttackerColor || ! $teamAColor || ! $teamBColor) {
            return null;
        }

        $otherColor = $firstHalfAttackerColor === $teamAColor ? $teamBColor : $teamAColor;

        if ($index < self::FIRST_HALF_ROUNDS) {
            return $firstHalfAttackerColor;
        }

        if ($index < self::FIRST_HALF_ROUNDS * 2) {
            return $otherColor;
        }

        $otIndex = $index - self::FIRST_HALF_ROUNDS * 2;

        return $otIndex % 2 === 0 ? $firstHalfAttackerColor : $otherColor;
    }

    /**
     * Detect, for a single round, whether a player was left alone against
     * one or more alive opponents (a clutch situation), and record the
     * outcome (won/lost) against the enemy count at the moment they were
     * left alone.
     */
    public function applyClutchStats(array &$agg, Collection $kills, Collection $teamByPuuid, Collection $rosterByColor, string $teamAColor, string $teamBColor, string $winningColor): void
    {
        $aliveSets = [
            $teamAColor => array_flip($rosterByColor[$teamAColor] ?? []),
            $teamBColor => array_flip($rosterByColor[$teamBColor] ?? []),
        ];
        $clutchFlagged = [];

        foreach ($kills as $kill) {
            $victimColor = $teamByPuuid[$kill['victim']] ?? null;
            if ($victimColor && isset($aliveSets[$victimColor][$kill['victim']])) {
                unset($aliveSets[$victimColor][$kill['victim']]);
            }

            foreach ([$teamAColor, $teamBColor] as $color) {
                if (isset($clutchFlagged[$color]) || count($aliveSets[$color]) !== 1) {
                    continue;
                }

                $enemyColor = $color === $teamAColor ? $teamBColor : $teamAColor;
                $enemyAlive = count($aliveSets[$enemyColor]);

                if ($enemyAlive >= 1) {
                    $clutchFlagged[$color] = [
                        'puuid' => array_key_first($aliveSets[$color]),
                        'n' => min($enemyAlive, 5),
                    ];
                }
            }
        }

        foreach ($clutchFlagged as $color => $clutch) {
            $puuid = $clutch['puuid'];
            $n = $clutch['n'];

            if (! isset($agg[$puuid])) {
                continue;
            }

            $agg[$puuid]["clutch_1v{$n}_total"]++;
            if ($winningColor === $color) {
                $agg[$puuid]["clutch_1v{$n}_won"]++;
            }
        }
    }

    /**
     * Detect trade kills/deaths for a round: a death is "traded" when a
     * teammate of the victim kills the killer within the trade window.
     */
    public function applyTradeStats(array &$agg, Collection $kills, Collection $teamByPuuid): void
    {
        foreach ($kills as $death) {
            $killerPuuid = $death['killer'];

            if (! $killerPuuid || ! isset($agg[$death['victim']])) {
                continue;
            }

            $victimTeam = $teamByPuuid[$death['victim']] ?? null;

            $revenge = $kills->first(fn ($k) => $k['victim'] === $killerPuuid
                && $k['killer'] !== null
                && ($teamByPuuid[$k['killer']] ?? null) === $victimTeam
                && $k['time'] >= $death['time']
                && $k['time'] <= $death['time'] + self::TRADE_WINDOW_MS);

            if ($revenge) {
                $agg[$death['victim']]['traded_deaths']++;
                if (isset($agg[$revenge['killer']])) {
                    $agg[$revenge['killer']]['trade_kills']++;
                }
            }
        }
    }

    public function emptyAdvancedStatsRow(): array
    {
        $row = [];

        for ($n = 1; $n <= 5; $n++) {
            $row["clutch_1v{$n}_won"] = 0;
            $row["clutch_1v{$n}_total"] = 0;
        }

        return array_merge($row, [
            'multikill_2k' => 0, 'multikill_3k' => 0, 'multikill_4k' => 0, 'multikill_5k' => 0,
            'trade_kills' => 0, 'traded_deaths' => 0,
            'plants' => 0, 'defuses' => 0,
            'pistol_won' => 0, 'pistol_played' => 0,
            'eco_won' => 0, 'eco_played' => 0,
            'force_won' => 0, 'force_played' => 0,
            'full_buy_won' => 0, 'full_buy_played' => 0,
            'post_plant_won' => 0, 'post_plant_played' => 0,
            'atk_rounds' => 0, 'atk_rounds_won' => 0, 'atk_kills' => 0, 'atk_kast_rounds' => 0,
            'def_rounds' => 0, 'def_rounds_won' => 0, 'def_kills' => 0, 'def_kast_rounds' => 0,
        ]);
    }

    /**
     * Compute per-player KAST%, ACS, ADR and HS% for the map (the "basic"
     * game_player_stats row), keyed by puuid. Callers add match/team/player
     * identifiers before persisting.
     *
     * @return array<string, array{kills:int,deaths:int,assists:int,acs:float,adr:float,kast_percentage:float,first_kills:int,first_deaths:int,headshot_percentage:float}>
     */
    public function computeBasicStats(Collection $players, Collection $rounds, Collection $roundKills, int $totalRounds): array
    {
        $teamByPuuid = $players->pluck('teamId', 'puuid');
        $rows = [];

        foreach ($players as $p) {
            $puuid = $p['puuid'];
            $stats = $p['stats'];

            $kastRounds = 0;
            $fk = 0;
            $fd = 0;
            $totalDamage = 0;
            $headshots = 0;
            $bodyshots = 0;
            $legshots = 0;

            foreach ($rounds as $index => $round) {
                $kills = $roundKills[$index];
                $playerRoundStat = collect($round['playerStats'])->firstWhere('puuid', $puuid);

                $killsInRound = $kills->where('killer', $puuid)->count();
                $assistsInRound = $kills->filter(fn ($k) => in_array($puuid, $k['assistants']))->count();

                $death = $kills->firstWhere('victim', $puuid);
                $survived = ! $death;

                $traded = false;
                if ($death && $death['killer']) {
                    $killerPuuid = $death['killer'];
                    $timeOfDeath = $death['time'];

                    $traded = $kills->contains(fn ($k) => $k['victim'] === $killerPuuid
                        && $k['killer'] !== null
                        && ($teamByPuuid[$k['killer']] ?? null) === ($teamByPuuid[$puuid] ?? null)
                        && $k['time'] >= $timeOfDeath
                        && $k['time'] <= $timeOfDeath + self::TRADE_WINDOW_MS);
                }

                if ($killsInRound > 0 || $assistsInRound > 0 || $survived || $traded) {
                    $kastRounds++;
                }

                $firstKill = $kills->first();
                if ($firstKill) {
                    if ($firstKill['killer'] === $puuid) {
                        $fk++;
                    }
                    if ($firstKill['victim'] === $puuid) {
                        $fd++;
                    }
                }

                foreach ($playerRoundStat['damage'] ?? [] as $damage) {
                    $totalDamage += $damage['damage'] ?? 0;
                    $headshots += $damage['headshots'] ?? 0;
                    $bodyshots += $damage['bodyshots'] ?? 0;
                    $legshots += $damage['legshots'] ?? 0;
                }
            }

            $kastPercentage = $totalRounds > 0 ? ($kastRounds / $totalRounds) * 100 : 0;
            $acs = $totalRounds > 0 ? round($stats['score'] / $totalRounds) : 0;
            $adr = $totalRounds > 0 ? round($totalDamage / $totalRounds) : 0;
            $totalShots = $headshots + $bodyshots + $legshots;
            $hsPercentage = $totalShots > 0 ? ($headshots / $totalShots) * 100 : 0;

            $rows[$puuid] = [
                'kills' => $stats['kills'],
                'deaths' => $stats['deaths'],
                'assists' => $stats['assists'],
                'acs' => $acs,
                'adr' => $adr,
                'kast_percentage' => round($kastPercentage, 2),
                'first_kills' => $fk,
                'first_deaths' => $fd,
                'headshot_percentage' => round($hsPercentage, 2),
            ];
        }

        return $rows;
    }

    /**
     * Compute clutches, multi-kills, trades, economy round outcomes,
     * plants/defuses/post-plant and ATK/DEF splits for every player on the
     * map, keyed by puuid, with atk/def KAST% already resolved.
     *
     * @return array<string, array<string, int|float>>
     */
    public function computeAdvancedStats(Collection $players, Collection $rounds, Collection $roundKills, ?string $teamAColor): array
    {
        $teamByPuuid = $players->pluck('teamId', 'puuid');
        $rosterByColor = $players->groupBy('teamId')->map(fn ($g) => $g->pluck('puuid')->all());
        $teamBColor = $teamAColor
            ? $rosterByColor->keys()->first(fn ($color) => $color !== $teamAColor)
            : null;
        $firstHalfAttackerColor = $this->firstHalfAttackerColor($rounds, $players);

        $agg = $players->pluck('puuid')
            ->mapWithKeys(fn ($puuid) => [$puuid => $this->emptyAdvancedStatsRow()])
            ->all();

        foreach ($rounds as $index => $round) {
            $kills = $roundKills[$index];
            $winningColor = $round['winningTeam'];
            $roundNumber = $round['roundNum'] + 1;
            $attackerColor = $this->attackerColorForRoundIndex($index, $firstHalfAttackerColor, $teamAColor, $teamBColor);
            $bombPlanter = $round['bombPlanter'] ?? null;
            $bombDefuser = $round['bombDefuser'] ?? null;
            $isPistol = in_array($roundNumber, self::PISTOL_ROUNDS);

            if ($teamAColor && $teamBColor) {
                $this->applyClutchStats($agg, $kills, $teamByPuuid, $rosterByColor, $teamAColor, $teamBColor, $winningColor);
            }

            $this->applyTradeStats($agg, $kills, $teamByPuuid);

            $teamLoadout = [];
            foreach ($round['playerStats'] ?? [] as $pStat) {
                $color = $teamByPuuid[$pStat['puuid']] ?? null;
                if ($color !== null) {
                    $teamLoadout[$color] = ($teamLoadout[$color] ?? 0) + ($pStat['economy']['loadoutValue'] ?? 0);
                }
            }

            foreach ($round['playerStats'] ?? [] as $pStat) {
                $puuid = $pStat['puuid'];

                if (! isset($agg[$puuid])) {
                    continue;
                }

                $color = $teamByPuuid[$puuid] ?? null;
                $won = $winningColor === $color;
                $killsInRound = $kills->where('killer', $puuid)->count();
                $assistsInRound = $kills->filter(fn ($k) => in_array($puuid, $k['assistants']))->count();
                $death = $kills->firstWhere('victim', $puuid);
                $survived = ! $death;

                $traded = false;
                if ($death && $death['killer']) {
                    $killerPuuid = $death['killer'];
                    $traded = $kills->contains(fn ($k) => $k['victim'] === $killerPuuid
                        && $k['killer'] !== null
                        && ($teamByPuuid[$k['killer']] ?? null) === $color
                        && $k['time'] >= $death['time']
                        && $k['time'] <= $death['time'] + self::TRADE_WINDOW_MS);
                }

                $kastThisRound = $killsInRound > 0 || $assistsInRound > 0 || $survived || $traded;

                if ($killsInRound >= 2 && $killsInRound <= 4) {
                    $agg[$puuid]["multikill_{$killsInRound}k"]++;
                } elseif ($killsInRound >= 5) {
                    $agg[$puuid]['multikill_5k']++;
                }

                $loadout = $teamLoadout[$color] ?? 0;

                if ($isPistol) {
                    $agg[$puuid]['pistol_played']++;
                    if ($won) {
                        $agg[$puuid]['pistol_won']++;
                    }
                } elseif ($loadout < self::TEAM_ECO_MAX_LOADOUT) {
                    $agg[$puuid]['eco_played']++;
                    if ($won) {
                        $agg[$puuid]['eco_won']++;
                    }
                } elseif ($loadout < self::TEAM_FORCE_MAX_LOADOUT) {
                    $agg[$puuid]['force_played']++;
                    if ($won) {
                        $agg[$puuid]['force_won']++;
                    }
                } else {
                    $agg[$puuid]['full_buy_played']++;
                    if ($won) {
                        $agg[$puuid]['full_buy_won']++;
                    }
                }

                if ($bombPlanter === $puuid) {
                    $agg[$puuid]['plants']++;
                }

                if ($bombDefuser === $puuid) {
                    $agg[$puuid]['defuses']++;
                }

                if ($bombPlanter && $attackerColor && $color === $attackerColor) {
                    $agg[$puuid]['post_plant_played']++;
                    if ($won) {
                        $agg[$puuid]['post_plant_won']++;
                    }
                }

                if ($attackerColor && $color === $attackerColor) {
                    $agg[$puuid]['atk_rounds']++;
                    $agg[$puuid]['atk_kills'] += $killsInRound;
                    if ($won) {
                        $agg[$puuid]['atk_rounds_won']++;
                    }
                    if ($kastThisRound) {
                        $agg[$puuid]['atk_kast_rounds']++;
                    }
                } elseif ($attackerColor) {
                    $agg[$puuid]['def_rounds']++;
                    $agg[$puuid]['def_kills'] += $killsInRound;
                    if ($won) {
                        $agg[$puuid]['def_rounds_won']++;
                    }
                    if ($kastThisRound) {
                        $agg[$puuid]['def_kast_rounds']++;
                    }
                }
            }
        }

        foreach ($agg as $puuid => &$row) {
            $atkKastRounds = $row['atk_kast_rounds'];
            $defKastRounds = $row['def_kast_rounds'];
            unset($row['atk_kast_rounds'], $row['def_kast_rounds']);

            $row['atk_kast_percentage'] = $row['atk_rounds'] > 0 ? round($atkKastRounds / $row['atk_rounds'] * 100, 2) : 0;
            $row['def_kast_percentage'] = $row['def_rounds'] > 0 ? round($defKastRounds / $row['def_rounds'] * 100, 2) : 0;
        }

        return $agg;
    }
}
