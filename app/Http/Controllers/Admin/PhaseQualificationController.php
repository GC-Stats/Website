<?php

/**
 * GC-Stats — Admin: phase qualification rules
 *
 * A rule says a rank range within a swiss/round_robin phase, or the
 * winner/loser of a specific bracket match, either advances to another
 * tournament phase (possibly in a different tournament) or receives a
 * final placement (e.g. "Champion", "3-4"). See PhaseQualification.
 *
 * Rank-based rules are managed from the source phase (tournaments.edit);
 * match-outcome rules are managed from the source match (matches.edit).
 * destroy() is shared by both, so it authorizes per-rule based on which
 * kind it actually is rather than via route middleware.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Matchs;
use App\Models\PhaseQualification;
use App\Models\Tournament;
use App\Models\TournamentPhase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PhaseQualificationController extends Controller
{
    public function store(Request $request, Tournament $tournament, TournamentPhase $phase): RedirectResponse
    {
        abort_unless($phase->tournament_id === $tournament->id, 404);
        abort_unless(in_array($phase->format, TournamentPhase::RANK_BASED_FORMATS, true), 422, 'Rank-based qualification rules only apply to swiss/round_robin phases.');

        $validated = $this->validateRule($request, $phase->id, [
            'rank_from' => ['required', 'integer', 'min:1'],
            'rank_to' => ['required', 'integer', 'min:1', 'gte:rank_from'],
        ]);

        PhaseQualification::create([
            'source_phase_id' => $phase->id,
            ...$validated,
        ]);

        activity('tournament')->causedBy($request->user())
            ->performedOn($phase)->log('phase_qualification.created');

        return back()->with('status', 'phase-qualification-created');
    }

    public function storeForMatch(Request $request, Tournament $tournament, Matchs $match): RedirectResponse
    {
        abort_unless($match->tournament_id === $tournament->id, 404);
        abort_unless($match->tournamentPhase?->format === 'bracket', 422, 'Match-outcome qualification rules only apply to bracket phases.');

        $validated = $this->validateRule($request, $match->phase_id, [
            'outcome' => ['required', 'string', Rule::in(['winner', 'loser'])],
        ]);

        PhaseQualification::create([
            'source_phase_id' => $match->phase_id,
            'source_match_id' => $match->id,
            ...$validated,
        ]);

        activity('tournament')->causedBy($request->user())
            ->performedOn($match)->log('phase_qualification.created');

        return back()->with('status', 'phase-qualification-created');
    }

    public function destroy(Request $request, Tournament $tournament, PhaseQualification $qualification): RedirectResponse
    {
        $this->ensureBelongsToTournament($tournament, $qualification);

        $isMatchBased = $qualification->source_match_id !== null;
        $permission = $isMatchBased ? 'matches.edit' : 'tournaments.edit';

        abort_unless($request->user()->can($permission), 403);

        $qualification->delete();

        activity('tournament')->causedBy($request->user())->log('phase_qualification.deleted');

        return back()->with('status', 'phase-qualification-deleted');
    }

    /**
     * Autocomplete for picking a destination phase across any tournament
     * (qualification can cross tournaments, e.g. regional -> major).
     */
    public function searchPhases(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $words = array_filter(preg_split('/\s+/', trim($validated['q'])));

        $phasesQuery = TournamentPhase::query()->with('tournament:id,name');

        foreach ($words as $word) {
            $wordTerm = '%'.$this->escapeLike($word).'%';
            $phasesQuery->where(fn ($q) => $q->where('name', 'like', $wordTerm)
                ->orWhereHas('tournament', fn ($t) => $t->where('name', 'like', $wordTerm)));
        }

        $phases = $phasesQuery->limit(10)->get(['id', 'tournament_id', 'name']);

        return response()->json($phases->map(fn ($phase) => [
            'id' => $phase->id,
            'label' => $phase->tournament->name.' — '.$phase->name,
        ])->values());
    }

    /**
     * @param  array<string, array<int, mixed>>  $sourceRules
     * @return array<string, mixed>
     */
    private function validateRule(Request $request, int $sourcePhaseId, array $sourceRules): array
    {
        $request->merge([
            'destination_phase_id' => $request->input('destination_phase_id') ?: null,
            'placement' => $request->input('placement') ?: null,
            'placement_label' => $request->input('placement_label') ?: null,
            'points' => $request->input('points') ?: null,
            'cash_prize_amount' => $request->input('cash_prize_amount') ?: null,
            'cash_prize_currency' => $request->input('cash_prize_currency') ?: null,
        ]);

        $validated = $request->validate([
            ...$sourceRules,
            'destination_type' => ['required', 'string', Rule::in(['phase', 'placement'])],
            'destination_phase_id' => ['required_if:destination_type,phase', 'nullable', 'integer', 'exists:tournament_phases,id'],
            'placement' => ['required_if:destination_type,placement', 'nullable', 'integer', 'min:1'],
            'placement_label' => ['required_if:destination_type,placement', 'nullable', 'string', 'max:50'],

            // Points/cash prize only make sense for a final placement — a phase-advancement
            // rule isn't itself a reward, so these are dropped below when destination_type=phase.
            'points' => ['nullable', 'integer', 'min:0'],
            'cash_prize_amount' => ['nullable', 'numeric', 'min:0'],
            'cash_prize_currency' => ['required_with:cash_prize_amount', 'nullable', 'string', 'regex:/^[A-Za-z]{3}$/'],
        ]);

        if (! empty($validated['cash_prize_currency'])) {
            $validated['cash_prize_currency'] = strtoupper($validated['cash_prize_currency']);
        }

        // A phase can't qualify into itself.
        if (($validated['destination_type'] === 'phase') && (int) $validated['destination_phase_id'] === $sourcePhaseId) {
            abort(422, 'A phase cannot qualify into itself.');
        }

        if ($validated['destination_type'] === 'phase') {
            $validated['placement'] = null;
            $validated['placement_label'] = null;
            $validated['points'] = null;
            $validated['cash_prize_amount'] = null;
            $validated['cash_prize_currency'] = null;
        } else {
            $validated['destination_phase_id'] = null;
        }

        return $validated;
    }

    private function ensureBelongsToTournament(Tournament $tournament, PhaseQualification $qualification): void
    {
        $tournamentId = $qualification->source_match_id
            ? $qualification->sourceMatch->tournament_id
            : $qualification->sourcePhase->tournament_id;

        abort_unless($tournamentId === $tournament->id, 404);
    }
}
