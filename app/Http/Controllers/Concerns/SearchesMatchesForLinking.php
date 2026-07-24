<?php

/**
 * GC-Stats — Tournament/match search for the streams & VODs "link" wizards
 *
 * Backs the two-step search (tournament, then match within it) used by
 * Admin\MatchStreamController and Admin\MatchVodController's create()
 * wizard pages — lets a user reach a match to link a channel/VOD to
 * without needing matches.view (which publishers never have): searching by
 * tournament name, then by either team's name within that tournament.
 *
 * A user holding the full site permission (see static::LINK_PERMISSION on
 * the using class) can search across every tournament; anyone else
 * (a publisher-scoped editor) is restricted to tournaments currently
 * flagged `active` — same "active" flag already used to gate public
 * visibility of a tournament (see MatchController@index).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Concerns;

use App\Models\Matchs;
use App\Models\Tournament;
use App\Support\MatchDisplay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait SearchesMatchesForLinking
{
    public function searchTournaments(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:100'],
        ]);

        $activeOnly = ! $request->user()->can(static::LINK_PERMISSION);

        $tournaments = Tournament::query()
            ->where('name', 'like', '%'.$this->escapeLike($validated['q']).'%')
            ->when($activeOnly, fn ($query) => $query->where('active', true))
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'name']);

        return response()->json($tournaments->map(fn ($tournament) => [
            'id' => $tournament->id,
            'label' => $tournament->name,
        ]));
    }

    /**
     * Returns every match of the tournament in one go (capped at 300 — a
     * bracket/swiss tournament's full match count never realistically
     * exceeds that) rather than paginating server-side: the wizard renders
     * these as a sortable table client-side (same columns as
     * admin/matches/index.blade.php — phase, round, teams, status, date),
     * so sorting/filtering happens instantly in the browser against the
     * already-fetched list instead of round-tripping per click.
     */
    public function searchMatchesInTournament(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tournament_id' => ['required', 'integer', 'exists:tournaments,id'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $tournament = Tournament::findOrFail($validated['tournament_id']);

        $activeOnly = ! $request->user()->can(static::LINK_PERMISSION);
        abort_if($activeOnly && ! $tournament->active, 403);

        $term = $validated['q'] ?? null;

        $matches = Matchs::query()
            ->where('tournament_id', $tournament->id)
            ->with([
                'teamA:id,name,short_name', 'teamB:id,name,short_name', 'tournamentPhase:id,name',
                'game_maps' => fn ($query) => $query->orderBy('order'),
            ])
            ->when($term, fn ($query) => $query->where(function ($inner) use ($term) {
                $inner->whereHas('teamA', fn ($teamQuery) => $teamQuery->where('name', 'like', '%'.$this->escapeLike($term).'%'))
                    ->orWhereHas('teamB', fn ($teamQuery) => $teamQuery->where('name', 'like', '%'.$this->escapeLike($term).'%'));
            }))
            ->orderByDesc('scheduled_at')
            ->limit(300)
            ->get();

        return response()->json($matches->map(fn ($match) => [
            'id' => $match->id,
            'tournament_id' => $match->tournament_id,
            'team_a' => MatchDisplay::teamName($match->teamA, $match->status),
            'team_b' => MatchDisplay::teamName($match->teamB, $match->status),
            'label' => MatchDisplay::teamName($match->teamA, $match->status).' vs '.MatchDisplay::teamName($match->teamB, $match->status),
            'phase' => $match->tournamentPhase->name ?? '',
            'round_name' => $match->round_name ?: '',
            'status' => $match->status,
            'scheduled_at' => MatchDisplay::isUnknownDate($match->scheduled_at) ? null : $match->scheduled_at->copy()->utc()->toIso8601String(),
            'maps' => $match->game_maps->map(fn ($map) => ['id' => $map->id, 'name' => $map->map_name])->values(),
        ]));
    }
}
