<?php

/**
 * GC-Stats — Head-to-head map comparison service
 *
 * Builds the per-map win/pick/ban profile of two teams, optionally scoped
 * to a tournament and/or a date range, for the "Face to Face" broadcast
 * widget (App\View\Components\Public\HeadToHead). Compares each team's
 * own map pool side by side (not just their direct confrontations), which
 * is what pre-veto scouting graphics show.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HeadToHeadService
{
    /**
     * When $teamBId is null, returns a solo profile for $teamAId only (no
     * comparison) — used as the default state of the Face to Face widget
     * before a second team is picked.
     */
    public function compare(int $teamAId, ?int $teamBId = null, ?int $tournamentId = null, ?Carbon $start = null, ?Carbon $end = null, ?string $patch = null): array
    {
        $teamA = Team::findOrFail($teamAId);
        $teamB = $teamBId ? Team::findOrFail($teamBId) : null;

        $profileA = $this->teamMapProfile($teamAId, $tournamentId, $start, $end);
        $profileB = $teamB ? $this->teamMapProfile($teamBId, $tournamentId, $start, $end) : collect();

        $mapNames = collect($profileA->keys())->merge($profileB->keys())->unique();

        if ($patch) {
            $activePool = $this->mapPoolForPatch($patch);

            if ($activePool->isNotEmpty()) {
                $mapNames = $mapNames->filter(fn ($mapName) => $activePool->contains($mapName));
            }
        }

        $mapNames = $mapNames->sort()->values();

        $maps = $mapNames->map(function ($mapName) use ($profileA, $profileB, $teamB) {
            return [
                'map_name' => $mapName,
                'team_a' => $profileA->get($mapName, $this->emptyMapStat()),
                'team_b' => $teamB ? $profileB->get($mapName, $this->emptyMapStat()) : null,
            ];
        })->values()->all();

        return [
            'team_a' => ['id' => $teamA->id, 'name' => $teamA->name, 'short_name' => $teamA->short_name, 'logo' => $teamA->logo],
            'team_b' => $teamB ? ['id' => $teamB->id, 'name' => $teamB->name, 'short_name' => $teamB->short_name, 'logo' => $teamB->logo] : null,
            'maps' => $maps,
        ];
    }

    /**
     * There's no static "active map pool" table in this app — a patch's
     * competitive pool is inferred from what was actually played under it,
     * site-wide, rather than hardcoded (patch-to-pool rotations aren't
     * tracked anywhere else). Falls back to showing every map the teams
     * have ever played if nothing is recorded for that patch yet (e.g. a
     * brand new patch with no completed maps logged).
     */
    private function mapPoolForPatch(string $patch): Collection
    {
        return DB::table('game_maps as gm')
            ->join('matches as m', 'm.id', '=', 'gm.match_id')
            ->where('m.patch', $patch)
            ->where('gm.is_completed', true)
            ->whereNotNull('gm.map_name')
            ->distinct()
            ->pluck('gm.map_name');
    }

    private function emptyMapStat(): array
    {
        return [
            'times_played' => 0, 'wins' => 0, 'win_pct' => null,
            'pick_count' => 0, 'pick_pct' => null,
            'ban_count' => 0, 'ban_pct' => null,
        ];
    }

    /**
     * @return Collection<string, array<string, mixed>>
     */
    private function teamMapProfile(int $teamId, ?int $tournamentId, ?Carbon $start, ?Carbon $end): Collection
    {
        $playedRows = DB::table('game_maps as gm')
            ->join('matches as m', 'm.id', '=', 'gm.match_id')
            ->where('gm.is_completed', true)
            ->whereNotNull('gm.map_name')
            ->where(function ($q) use ($teamId) {
                $q->where('m.team_a_id', $teamId)->orWhere('m.team_b_id', $teamId);
            })
            ->when($tournamentId, fn ($q) => $q->where('gm.tournament_id', $tournamentId))
            ->when($start && $end, fn ($q) => $q->whereBetween('m.scheduled_at', [$start, $end]))
            ->select('gm.map_name', 'gm.team_a_score', 'gm.team_b_score', 'm.team_a_id', 'm.team_b_id')
            ->get()
            ->groupBy('map_name');

        $timesPlayed = $playedRows->map->count();

        $wins = $playedRows->map(function ($rows) use ($teamId) {
            return $rows->filter(function ($row) use ($teamId) {
                $isTeamA = $row->team_a_id == $teamId;
                $ownScore = $isTeamA ? $row->team_a_score : $row->team_b_score;
                $oppScore = $isTeamA ? $row->team_b_score : $row->team_a_score;

                return ($ownScore ?? 0) > ($oppScore ?? 0);
            })->count();
        });

        $vetoRows = DB::table('match_vetos as mv')
            ->join('matches as m', 'm.id', '=', 'mv.match_id')
            ->when($tournamentId, fn ($q) => $q->where('m.tournament_id', $tournamentId))
            ->when($start && $end, fn ($q) => $q->whereBetween('m.scheduled_at', [$start, $end]))
            ->where('mv.team_id', $teamId)
            ->select('mv.map_name', 'mv.type')
            ->get();

        $pickCounts = $vetoRows->where('type', 'pick')->groupBy('map_name')->map->count();
        $banCounts = $vetoRows->where('type', 'ban')->groupBy('map_name')->map->count();
        $totalPicks = $pickCounts->sum();
        $totalBans = $banCounts->sum();

        $mapNames = $timesPlayed->keys()
            ->merge($pickCounts->keys())
            ->merge($banCounts->keys())
            ->unique();

        return $mapNames->mapWithKeys(function ($mapName) use ($timesPlayed, $wins, $pickCounts, $banCounts, $totalPicks, $totalBans) {
            $played = $timesPlayed->get($mapName, 0);
            $won = $wins->get($mapName, 0);
            $picks = $pickCounts->get($mapName, 0);
            $bans = $banCounts->get($mapName, 0);

            return [$mapName => [
                'times_played' => $played,
                'wins' => $won,
                'win_pct' => $played > 0 ? round($won / $played * 100, 1) : null,
                'pick_count' => $picks,
                'pick_pct' => $totalPicks > 0 ? round($picks / $totalPicks * 100, 1) : null,
                'ban_count' => $bans,
                'ban_pct' => $totalBans > 0 ? round($bans / $totalBans * 100, 1) : null,
            ]];
        });
    }
}
