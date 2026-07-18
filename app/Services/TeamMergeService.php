<?php

/**
 * GC-Stats — Team deletion & merge service
 *
 * Cross-table "team surgery" that doesn't belong in TeamProfileService
 * (scoped to editable profile fields/logo only): deleting a team outright,
 * and merging one team's data into another. Kept together since both
 * involve the same set of related tables (roster, team-scoped roles,
 * logos, tournament participation, news tags).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use App\Exceptions\TeamHasMatchesException;
use App\Models\Logo;
use App\Models\Team;
use App\Models\User;
use App\Support\PermissionTeam;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class TeamMergeService
{
    public function __construct(
        private readonly LogoUploadService $logoUploadService,
        private readonly RosterService $rosterService,
        private readonly TeamRoleService $teamRoleService,
    ) {}

    /**
     * A team with any recorded match (as either side) protects stats
     * history and cannot be deleted outright.
     */
    public function hasMatches(Team $team): bool
    {
        return DB::table('matches')->where('team_a_id', $team->id)->orWhere('team_b_id', $team->id)->exists();
    }

    /**
     * Delete a team and everything it owns.
     *
     * @throws TeamHasMatchesException if $team has recorded match history
     */
    public function delete(Team $team, User $actor): void
    {
        if ($this->hasMatches($team)) {
            throw new TeamHasMatchesException;
        }

        $teamId = $team->id;
        $teamName = $team->name;

        DB::transaction(function () use ($team) {
            DB::table('player_team')->where('team_id', $team->id)->delete();

            PermissionTeam::use($team->id);
            Role::where('team_id', $team->id)->get()->each->delete();
            PermissionTeam::global();

            foreach ($team->logos as $logo) {
                $this->logoUploadService->deleteFiles('teams', $logo->id);
            }
            $team->logos()->delete();

            DB::table('tournament_teams')->where('team_id', $team->id)->delete();
            DB::table('news_relations')->where('relationable_type', 'team')->where('relationable_id', $team->id)->delete();

            $team->delete();
        });

        Cache::tags(["team_{$teamId}"])->flush();

        activity('team')->causedBy($actor)
            ->withProperties(['team_id' => $teamId, 'name' => $teamName])
            ->log('team.deleted');
    }

    /**
     * Merge specific items of $source's data into $target. $target's own
     * profile fields are left untouched — only the checked items move.
     * $source itself is never deleted here; the admin deletes it
     * separately afterward once it's empty enough (subject to the same
     * hasMatches() block as any other team).
     *
     * There is no standalone "matches" selection: a match always belongs
     * to a tournament (non-nullable FK), so a match only moves when the
     * tournament it was played in is itself selected — selecting a
     * tournament pulls both the tournament_teams row and every one of
     * $source's matches within that tournament over to $target.
     *
     * @param  array{roster?: list<int>, tournaments?: list<int>, news?: list<int>, logos?: list<string>, roles?: list<string>}  $selection
     */
    public function merge(Team $source, Team $target, array $selection, User $actor): void
    {
        DB::transaction(function () use ($source, $target, $selection) {
            if (! empty($selection['roster'])) {
                $this->mergeRoster($source, $target, $selection['roster']);
            }

            if (! empty($selection['tournaments'])) {
                $this->mergeTournaments($source, $target, $selection['tournaments']);
            }

            if (! empty($selection['news'])) {
                $this->mergeNews($source, $target, $selection['news']);
            }

            if (! empty($selection['logos'])) {
                Logo::where('entity_type', 'team')->where('entity_id', $source->id)
                    ->whereIn('id', $selection['logos'])
                    ->update(['entity_id' => $target->id]);
            }

            if (! empty($selection['roles'])) {
                $this->mergeRoles($source, $target, $selection['roles']);
            }
        });

        Cache::tags(["team_{$source->id}"])->flush();
        Cache::tags(["team_{$target->id}"])->flush();

        activity('team')->causedBy($actor)
            ->withProperties(['source_id' => $source->id, 'target_id' => $target->id, 'selection' => $selection])
            ->log('team.merged');
    }

    /**
     * Moves each selected `player_team` row (by pivot id, current or
     * historical) to $target via direct, targeted UPDATEs rather than
     * RosterService::addMember()/deleteEntry() — those rewrite the *entire*
     * target roster on every call (built for the single-edit case), which
     * turns a merge into O(active entries x target roster size) queries.
     * Active rows (left_at null) still close out any other active row the
     * player holds elsewhere first, preserving the "active on at most one
     * team" invariant. Historical (already-left) rows carry no such
     * conflict, so they're just re-pointed to $target directly, keeping
     * their original joined_at/left_at.
     *
     * @param  list<int>  $entryIds
     */
    private function mergeRoster(Team $source, Team $target, array $entryIds): void
    {
        $entries = $this->rosterService->history($source->id)->whereIn('id', $entryIds);

        $affectedPlayerIds = [];

        foreach ($entries as $entry) {
            if ($entry->left_at === null) {
                DB::table('player_team')
                    ->where('player_id', $entry->player_id)
                    ->where('team_id', '!=', $source->id)
                    ->whereNull('left_at')
                    ->update(['left_at' => $entry->joined_at, 'updated_at' => now()]);
            }

            DB::table('player_team')->where('id', $entry->id)->update(['team_id' => $target->id, 'updated_at' => now()]);

            $affectedPlayerIds[] = $entry->player_id;
        }

        foreach (array_unique($affectedPlayerIds) as $playerId) {
            Cache::tags(["player_{$playerId}"])->flush();
        }
    }

    /**
     * For each selected tournament: re-point $source's tournament_teams
     * row to $target (dropping it instead if $target already has one for
     * that tournament), and move every one of $source's matches within
     * that tournament over to $target.
     *
     * @param  list<int>  $tournamentIds
     */
    private function mergeTournaments(Team $source, Team $target, array $tournamentIds): void
    {
        $targetTournamentIds = DB::table('tournament_teams')->where('team_id', $target->id)
            ->whereIn('tournament_id', $tournamentIds)->pluck('tournament_id');

        DB::table('tournament_teams')->where('team_id', $source->id)
            ->whereIn('tournament_id', $tournamentIds)->whereIn('tournament_id', $targetTournamentIds)->delete();

        DB::table('tournament_teams')->where('team_id', $source->id)
            ->whereIn('tournament_id', $tournamentIds)->whereNotIn('tournament_id', $targetTournamentIds)
            ->update(['team_id' => $target->id]);

        DB::table('matches')->where('team_a_id', $source->id)->whereIn('tournament_id', $tournamentIds)->update(['team_a_id' => $target->id]);
        DB::table('matches')->where('team_b_id', $source->id)->whereIn('tournament_id', $tournamentIds)->update(['team_b_id' => $target->id]);
    }

    /**
     * @param  list<int>  $newsIds
     */
    private function mergeNews(Team $source, Team $target, array $newsIds): void
    {
        $targetNewsIds = DB::table('news_relations')
            ->where('relationable_type', 'team')->where('relationable_id', $target->id)
            ->whereIn('news_id', $newsIds)->pluck('news_id');

        DB::table('news_relations')
            ->where('relationable_type', 'team')->where('relationable_id', $source->id)
            ->whereIn('news_id', $newsIds)->whereIn('news_id', $targetNewsIds)
            ->delete();

        DB::table('news_relations')
            ->where('relationable_type', 'team')->where('relationable_id', $source->id)
            ->whereIn('news_id', $newsIds)->whereNotIn('news_id', $targetNewsIds)
            ->update(['relationable_id' => $target->id]);
    }

    /**
     * Moves each selected (role, user) assignment from one of $source's
     * team-scoped roles to the equivalent role on $target, provisioning
     * $target's roles first if this is its first assignment. Only the
     * team_owner/team_manager/team_editor role *names* carry over — the
     * role rows themselves stay put, since each team always has its own
     * (see TeamRoleService).
     *
     * $pairs entries are pre-validated by the DB lookup below (matched
     * against model_has_roles for $source) rather than trusted as-is, so a
     * tampered "roleId:userId" pair that doesn't correspond to a real
     * assignment on $source is silently skipped.
     *
     * @param  list<string>  $pairs  each formatted "{role_id}:{user_id}"
     */
    private function mergeRoles(Team $source, Team $target, array $pairs): void
    {
        $roleIds = [];
        $userIds = [];

        foreach ($pairs as $pair) {
            [$roleId, $userId] = array_pad(explode(':', $pair, 2), 2, null);

            if (ctype_digit((string) $roleId) && ctype_digit((string) $userId)) {
                $roleIds[] = (int) $roleId;
                $userIds[] = (int) $userId;
            }
        }

        if (empty($roleIds)) {
            return;
        }

        $this->teamRoleService->ensureRolesExist($target);

        $sourceRolesById = Role::where('team_id', $source->id)->whereIn('id', $roleIds)->get()->keyBy('id');
        $targetRolesByName = Role::where('team_id', $target->id)->get()->keyBy('name');

        $assignments = DB::table('model_has_roles')
            ->where('team_id', $source->id)
            ->where('model_type', User::class)
            ->whereIn('role_id', $roleIds)
            ->whereIn('model_id', $userIds)
            ->get(['role_id', 'model_id']);

        foreach ($assignments as $assignment) {
            $sourceRole = $sourceRolesById->get($assignment->role_id);
            $targetRole = $sourceRole ? $targetRolesByName->get($sourceRole->name) : null;
            $user = $targetRole ? User::find($assignment->model_id) : null;

            if (! $user) {
                continue;
            }

            PermissionTeam::use($source->id);
            $user->removeRole($sourceRole);

            PermissionTeam::use($target->id);
            $user->assignRole($targetRole);
        }

        PermissionTeam::global();
    }
}
