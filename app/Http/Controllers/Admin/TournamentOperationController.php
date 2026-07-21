<?php

/**
 * GC-Stats — Admin: tournament operations
 *
 * Bulk/utility actions scoped to a tournament, kept off the regular
 * matches CRUD pages since they're mass-write or raw-relay tools rather
 * than single-record edits: bulk patch change, bulk match creation, and
 * a match-cache-purge passthrough to the Riot relay. Each action has its
 * own permission plus a `.finished` sibling (same pattern as the
 * matches.* / maps.* permissions, see MatchController) since a finished
 * tournament still needs protecting from bulk edits unless explicitly
 * allowed.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Matchs;
use App\Models\Tournament;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class TournamentOperationController extends Controller
{
    public function index(Request $request, Tournament $tournament): View
    {
        abort_unless(
            $request->user()->canAny(['operations.patch', 'operations.bulk-create', 'operations.cache-purge']),
            403
        );

        return view('admin.tournaments.operations', [
            'tournament' => $tournament,
            'phases' => $tournament->phases,
            'riotRegions' => collect(config('regions.riot_api'))->unique()->values()->push('esports')->values(),
        ]);
    }

    /**
     * Scope defaults to every match in the tournament; `phase_id` and/or
     * the date range narrow it down to a single phase and/or a scheduling
     * window, so a patch bump mid-tournament doesn't have to be applied
     * (or re-applied) to matches that already happened on the old patch.
     */
    public function patchAll(Request $request, Tournament $tournament): RedirectResponse
    {
        $this->requireAllowed($request, $tournament, 'operations.patch');

        $validated = $request->validate([
            'patch' => ['required', 'string', 'max:20'],
            'phase_id' => ['sometimes', 'nullable', 'integer', 'exists:tournament_phases,id'],
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to' => ['sometimes', 'nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $query = Matchs::where('tournament_id', $tournament->id)
            ->when(! empty($validated['phase_id']), fn ($q) => $q->where('phase_id', $validated['phase_id']))
            ->when(! empty($validated['date_from']), fn ($q) => $q->whereDate('scheduled_at', '>=', $validated['date_from']))
            ->when(! empty($validated['date_to']), fn ($q) => $q->whereDate('scheduled_at', '<=', $validated['date_to']));

        $count = $query->update(['patch' => $validated['patch']]);

        activity('tournament')->causedBy($request->user())
            ->performedOn($tournament)
            ->withProperties([
                'patch' => $validated['patch'],
                'matches' => $count,
                'phase_id' => $validated['phase_id'] ?? null,
                'date_from' => $validated['date_from'] ?? null,
                'date_to' => $validated['date_to'] ?? null,
            ])
            ->log('operations.patch_applied');

        return back()->with('status', 'operations-patch-applied');
    }

    public function bulkCreate(Request $request, Tournament $tournament): RedirectResponse
    {
        $this->requireAllowed($request, $tournament, 'operations.bulk-create');

        $validated = $request->validate([
            'phase_id' => ['required', 'integer', Rule::exists('tournament_phases', 'id')->where('tournament_id', $tournament->id)],
            'count' => ['required', 'integer', 'min:1', 'max:100'],
            'scheduled_at' => ['required', 'date'],
            'best_of' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $created = DB::transaction(function () use ($validated, $tournament) {
            $startOrder = (int) Matchs::where('phase_id', $validated['phase_id'])->max('match_order') + 1;

            for ($i = 0; $i < $validated['count']; $i++) {
                Matchs::create([
                    'tournament_id' => $tournament->id,
                    'phase_id' => $validated['phase_id'],
                    'team_a_id' => null,
                    'team_b_id' => null,
                    'scheduled_at' => $validated['scheduled_at'],
                    'status' => 'upcoming',
                    'best_of' => $validated['best_of'],
                    'match_order' => $startOrder + $i,
                ]);
            }

            return $validated['count'];
        });

        activity('tournament')->causedBy($request->user())
            ->performedOn($tournament)
            ->withProperties(['phase_id' => $validated['phase_id'], 'count' => $created])
            ->log('operations.matches_bulk_created');

        return back()->with('status', 'operations-bulk-created');
    }

    public function purgeCache(Request $request, Tournament $tournament): RedirectResponse
    {
        $this->requireAllowed($request, $tournament, 'operations.cache-purge');

        $allowedRegions = collect(config('regions.riot_api'))->unique()->values()->push('esports')->values()->all();

        $validated = $request->validate([
            'region' => ['required', 'string', Rule::in($allowedRegions)],
            'api_match_id' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9_-]+$/'],
        ]);

        $relayUrl = rtrim(config('services.riot.relay_url'), '/');

        $response = Http::withHeaders(['Authorization' => config('services.riot.relay_token')])
            ->post("{$relayUrl}/match/{$validated['region']}/{$validated['api_match_id']}/renew");

        $cacheStatus = $response->header('X-Cache');
        $preserved = $response->header('X-Cache-Preserved') === 'true';

        activity('tournament')->causedBy($request->user())
            ->performedOn($tournament)
            ->withProperties(['region' => $validated['region'], 'api_match_id' => $validated['api_match_id'], 'cache_status' => $cacheStatus, 'preserved' => $preserved])
            ->log('operations.cache_purged');

        return back()->with('purgeResult', [
            'renewed' => $cacheStatus === 'RENEWED',
            'preserved' => $preserved,
            'status' => $cacheStatus,
        ]);
    }

    private function requireAllowed(Request $request, Tournament $tournament, string $permission): void
    {
        abort_unless(
            $tournament->status !== 'finished' || $request->user()->can("{$permission}.finished"),
            403,
            "Only a user with {$permission}.finished can run this operation on a finished tournament."
        );
    }
}
