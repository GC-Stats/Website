<?php

/**
 * GC-Stats — Player merge service
 *
 * Merges specific items of one player's data into another. Mirrors
 * TeamMergeService's merge() — see its docblock — but scoped to what a
 * Player actually owns: team history (player_team, the roster-equivalent
 * from the player's side), match stats, logos and news tags. Players have
 * no per-entity roles or tournament participation of their own, so those
 * TeamMergeService categories have no equivalent here.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use App\Exceptions\PlayerHasMatchesException;
use App\Models\Logo;
use App\Models\Player;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PlayerMergeService
{
    public function __construct(private readonly LogoUploadService $logoUploadService) {}

    /**
     * A player with any recorded match stats protects that history and
     * cannot be deleted outright. Mirrors TeamMergeService::hasMatches().
     */
    public function hasMatches(Player $player): bool
    {
        return DB::table('game_player_stats')->where('player_id', $player->id)->exists();
    }

    /**
     * Delete a player, guarded by hasMatches(). Related rows (player_team,
     * logos, news_relations) are left to their DB cascade constraints —
     * unlike TeamMergeService::delete(), which cleans those up manually
     * because a team also owns team-scoped roles with no such constraint.
     *
     * @throws PlayerHasMatchesException if $player has recorded match stats
     */
    public function delete(Player $player, User $actor): void
    {
        if ($this->hasMatches($player)) {
            throw new PlayerHasMatchesException;
        }

        $playerId = $player->id;
        $handle = $player->handle;

        $affectedTeamIds = DB::table('player_team')->where('player_id', $player->id)->pluck('team_id')->unique();

        foreach ($player->logos as $logo) {
            $this->logoUploadService->deleteFiles('players', $logo->id);
        }

        $player->delete();

        Cache::tags(["player_{$playerId}"])->flush();

        foreach ($affectedTeamIds as $teamId) {
            Cache::tags(["team_{$teamId}"])->flush();
        }

        activity('player')->causedBy($actor)
            ->withProperties(['player_id' => $playerId, 'handle' => $handle])
            ->log('player.deleted');
    }

    /**
     * $source itself is never deleted here — only the checked items move.
     *
     * @param  array{teams?: list<int>, news?: list<int>, logos?: list<string>, stats?: list<int>}  $selection
     */
    public function merge(Player $source, Player $target, array $selection, User $actor): void
    {
        DB::transaction(function () use ($source, $target, $selection) {
            if (! empty($selection['teams'])) {
                $this->mergeTeams($source, $target, $selection['teams']);
            }

            if (! empty($selection['news'])) {
                $this->mergeNews($source, $target, $selection['news']);
            }

            if (! empty($selection['logos'])) {
                Logo::where('entity_type', 'player')->where('entity_id', $source->id)
                    ->whereIn('id', $selection['logos'])
                    ->update(['entity_id' => $target->id]);
            }

            if (! empty($selection['stats'])) {
                $this->mergeStats($source, $target, $selection['stats']);
            }
        });

        Cache::tags(["player_{$source->id}"])->flush();
        Cache::tags(["player_{$target->id}"])->flush();

        activity('player')->causedBy($actor)
            ->withProperties(['source_id' => $source->id, 'target_id' => $target->id, 'selection' => $selection])
            ->log('player.merged');
    }

    /**
     * Moves each selected `player_team` row (by pivot id, current or
     * historical) from $source to $target. Mirrors
     * TeamMergeService::mergeRoster(), direction-flipped: a player can only
     * be active on one team at a time, so a moved active row (left_at null)
     * closes out any other active row $target already holds elsewhere
     * first — otherwise the merge would leave $target active on two teams.
     *
     * @param  list<int>  $entryIds
     */
    private function mergeTeams(Player $source, Player $target, array $entryIds): void
    {
        $entries = DB::table('player_team')->where('player_id', $source->id)->whereIn('id', $entryIds)->get();

        $affectedTeamIds = [];

        foreach ($entries as $entry) {
            if ($entry->left_at === null) {
                DB::table('player_team')
                    ->where('player_id', $target->id)
                    ->whereNull('left_at')
                    ->update(['left_at' => $entry->joined_at, 'updated_at' => now()]);
            }

            DB::table('player_team')->where('id', $entry->id)->update(['player_id' => $target->id, 'updated_at' => now()]);

            $affectedTeamIds[] = $entry->team_id;
        }

        foreach (array_unique($affectedTeamIds) as $teamId) {
            Cache::tags(["team_{$teamId}"])->flush();
        }
    }

    /**
     * @param  list<int>  $newsIds
     */
    private function mergeNews(Player $source, Player $target, array $newsIds): void
    {
        $targetNewsIds = DB::table('news_relations')
            ->where('relationable_type', 'player')->where('relationable_id', $target->id)
            ->whereIn('news_id', $newsIds)->pluck('news_id');

        DB::table('news_relations')
            ->where('relationable_type', 'player')->where('relationable_id', $source->id)
            ->whereIn('news_id', $newsIds)->whereIn('news_id', $targetNewsIds)
            ->delete();

        DB::table('news_relations')
            ->where('relationable_type', 'player')->where('relationable_id', $source->id)
            ->whereIn('news_id', $newsIds)->whereNotIn('news_id', $targetNewsIds)
            ->update(['relationable_id' => $target->id]);
    }

    /**
     * Moves each selected `game_player_stats` row from $source to $target,
     * along with every other per-map/per-round table keyed on the same
     * (game_map_id, player) pair — game_player_advanced_stats,
     * game_map_round_player_stats, game_map_round_kills (killer, victim and
     * the assistant_player_ids json list) and game_map_round_damages
     * (attacker, receiver). A stat row is keyed one-per-(map, player,
     * agent), so dedup against $target must happen at that granularity —
     * checking only match_id would skip an entire BO3 (all of its maps)
     * whenever $target already had a row for just one map of it.
     *
     * @param  list<int>  $statIds
     */
    private function mergeStats(Player $source, Player $target, array $statIds): void
    {
        $sourceStats = DB::table('game_player_stats')->where('player_id', $source->id)
            ->whereIn('id', $statIds)->get(['id', 'game_map_id', 'agent_name']);

        if ($sourceStats->isEmpty()) {
            return;
        }

        $targetMapAgents = DB::table('game_player_stats')->where('player_id', $target->id)
            ->whereIn('game_map_id', $sourceStats->pluck('game_map_id')->unique())
            ->get(['game_map_id', 'agent_name'])
            ->map(fn ($row) => "{$row->game_map_id}|{$row->agent_name}");

        $movable = $sourceStats->reject(fn ($row) => $targetMapAgents->contains("{$row->game_map_id}|{$row->agent_name}"));

        if ($movable->isEmpty()) {
            return;
        }

        $mapIds = $movable->pluck('game_map_id')->unique()->values();

        DB::table('game_player_stats')->whereIn('id', $movable->pluck('id'))->update(['player_id' => $target->id]);

        DB::table('game_player_advanced_stats')->where('player_id', $source->id)
            ->whereIn('game_map_id', $mapIds)->update(['player_id' => $target->id]);

        $roundIds = DB::table('game_map_rounds')->whereIn('game_map_id', $mapIds)->pluck('id');

        DB::table('game_map_round_player_stats')->where('player_id', $source->id)
            ->whereIn('game_map_round_id', $roundIds)->update(['player_id' => $target->id]);

        DB::table('game_map_round_kills')->where('killer_player_id', $source->id)
            ->whereIn('game_map_round_id', $roundIds)->update(['killer_player_id' => $target->id]);

        DB::table('game_map_round_kills')->where('victim_player_id', $source->id)
            ->whereIn('game_map_round_id', $roundIds)->update(['victim_player_id' => $target->id]);

        DB::table('game_map_round_damages')->where('attacker_player_id', $source->id)
            ->whereIn('game_map_round_id', $roundIds)->update(['attacker_player_id' => $target->id]);

        DB::table('game_map_round_damages')->where('receiver_player_id', $source->id)
            ->whereIn('game_map_round_id', $roundIds)->update(['receiver_player_id' => $target->id]);

        $assistRows = DB::table('game_map_round_kills')
            ->whereIn('game_map_round_id', $roundIds)
            ->whereJsonContains('assistant_player_ids', $source->id)
            ->get(['id', 'assistant_player_ids']);

        foreach ($assistRows as $row) {
            $ids = collect(json_decode($row->assistant_player_ids, true))
                ->map(fn ($id) => $id === $source->id ? $target->id : $id)
                ->unique()->values()->all();

            DB::table('game_map_round_kills')->where('id', $row->id)->update(['assistant_player_ids' => json_encode($ids)]);
        }
    }
}
