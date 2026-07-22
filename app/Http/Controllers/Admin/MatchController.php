<?php

/**
 * GC-Stats — Admin: matches
 *
 * CRUD over a tournament's matches, its veto (map pick/ban) sequence, and
 * Liquipedia wikicode import. Editing a match that is itself finished, or
 * that belongs to a finished tournament, requires the `.finished` sibling
 * of whichever permission is being exercised (e.g. `matches.edit.finished`
 * on top of `matches.edit`) — see requireEditable() / isLockedFor().
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameMap;
use App\Models\Matchs;
use App\Models\Tournament;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MatchController extends Controller
{
    public const MAP_POOL = ['Abyss', 'Ascent', 'Bind', 'Breeze', 'Corrode', 'Fracture', 'Haven', 'Icebox', 'Lotus', 'Pearl', 'Split', 'Summit', 'Sunset'];

    /** Default veto sequence per best-of format, used to pre-fill a brand new (empty) veto. */
    private const VETO_DEFAULTS_BY_FORMAT = [
        1 => ['ban', 'ban', 'ban', 'ban', 'ban', 'ban', 'decider'],
        3 => ['ban', 'ban', 'pick', 'pick', 'ban', 'ban', 'decider'],
        5 => ['ban', 'ban', 'pick', 'pick', 'pick', 'pick', 'decider'],
    ];

    private const SORTABLE = ['round_name', 'phase', 'team', 'date', 'status'];

    public function index(Tournament $tournament, Request $request): View
    {
        $sort = $request->query('sort', session('matches.sort', 'date'));
        $direction = $request->query('direction', session('matches.direction', 'desc'));

        if (! in_array($sort, self::SORTABLE, true)) {
            $sort = 'date';
        }
        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        session(['matches.sort' => $sort, 'matches.direction' => $direction]);

        $query = Matchs::query()
            ->where('tournament_id', $tournament->id)
            ->with(['teamA:id,name,short_name', 'teamB:id,name,short_name', 'tournamentPhase:id,name']);

        if ($request->filled('team')) {
            $team = $request->input('team');
            $query->where(fn ($q) => $q->where('team_a_id', $team)->orWhere('team_b_id', $team));
        }
        if ($request->filled('phase')) {
            $query->where('phase_id', $request->input('phase'));
        }
        if ($request->filled('round_name')) {
            $query->where('round_name', 'like', '%'.$this->escapeLike($request->input('round_name')).'%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('date')) {
            $query->whereDate('scheduled_at', $request->input('date'));
        }

        match ($sort) {
            'phase' => $query->orderBy('phase_id', $direction),
            'round_name' => $query->orderBy('round_name', $direction),
            'team' => $query->orderBy('team_a_id', $direction),
            'status' => $query->orderBy('status', $direction),
            default => $query->orderBy('scheduled_at', $direction),
        };

        $matches = $query->paginate(25)->withQueryString();

        return view('admin.matches.index', [
            'tournament' => $tournament,
            'matches' => $matches,
            'team' => $request->get('team', ''),
            'phase' => $request->get('phase', ''),
            'round_name' => $request->get('round_name', ''),
            'status' => $request->get('status', ''),
            'date' => $request->get('date', ''),
            'sort' => $sort,
            'direction' => $direction,
            'phases' => $tournament->phases,
            'teams' => $tournament->teams,
            'sticky' => session('matches.sticky.'.$tournament->id, []),
        ]);
    }

    public function show(Tournament $tournament, Matchs $match): View
    {
        $this->ensureBelongsToTournament($tournament, $match);

        $match->load([
            'teamA', 'teamB', 'tournamentPhase', 'game_maps' => fn ($q) => $q->orderBy('order'),
            'qualifications.destinationPhase.tournament',
        ]);

        return view('admin.matches.show', [
            'tournament' => $tournament,
            'match' => $match,
        ]);
    }

    public function edit(Tournament $tournament, Matchs $match): View
    {
        $this->ensureBelongsToTournament($tournament, $match);

        return view('admin.matches.edit', [
            'tournament' => $tournament,
            'match' => $match,
            'phases' => $tournament->phases,
            'teams' => $tournament->teams,
            'importResults' => session('importResults', []),
        ]);
    }

    public function store(Request $request, Tournament $tournament): RedirectResponse
    {
        abort_unless(
            $tournament->status !== 'finished' || $request->user()->can('matches.create.finished'),
            403,
            'Only a user with matches.create.finished can create a match in a finished tournament.'
        );

        $validated = $this->validateMatch($request);
        $validated['tournament_id'] = $tournament->id;

        $match = Matchs::create($validated);

        session(['matches.sticky.'.$tournament->id => [
            'phase_id' => $validated['phase_id'],
            'best_of' => $validated['best_of'] ?? null,
        ]]);

        activity('tournament')->causedBy($request->user())
            ->performedOn($match)->log('match.created');

        return redirect()->route('admin.matches.index', $tournament)->with('status', 'match-created');
    }

    public function update(Request $request, Tournament $tournament, Matchs $match): RedirectResponse
    {
        $this->requireEditable($request, $tournament, $match, 'matches.edit');

        $validated = $this->validateMatch($request, true);

        $match->update($validated);

        activity('tournament')->causedBy($request->user())
            ->performedOn($match)->log('match.updated');

        return redirect()->route('admin.matches.show', [$tournament, $match])->with('status', 'match-updated');
    }

    public function destroy(Request $request, Tournament $tournament, Matchs $match): RedirectResponse
    {
        $this->requireEditable($request, $tournament, $match, 'matches.delete');

        $match->delete();

        activity('tournament')->causedBy($request->user())->log('match.deleted');

        return redirect()->route('admin.matches.index', $tournament)->with('status', 'match-deleted');
    }

    public function editVeto(Tournament $tournament, Matchs $match): View
    {
        $this->ensureBelongsToTournament($tournament, $match);

        $match->load(['teamA', 'teamB', 'map_bans' => fn ($q) => $q->orderBy('order')]);

        $existing = $match->map_bans->values();
        $prefilledTypes = $existing->isEmpty() ? (self::VETO_DEFAULTS_BY_FORMAT[$match->best_of] ?? null) : null;

        $vetoSlots = [];

        for ($i = 0; $i < 7; $i++) {
            $veto = $existing->get($i);

            $vetoSlots[] = [
                'team' => $veto ? (string) $veto->team_id : 'none',
                'map_name' => $veto->map_name ?? 'none',
                'type' => $prefilledTypes[$i] ?? ($veto->type ?? 'none'),
                'side' => $veto->side ?? '',
                'side_picked_by' => $veto && $veto->side_picked_by ? (string) $veto->side_picked_by : '',
            ];
        }

        return view('admin.matches.veto', [
            'tournament' => $tournament,
            'match' => $match,
            'mapPool' => self::MAP_POOL,
            'vetoSlots' => $vetoSlots,
        ]);
    }

    /**
     * Wipe a match back to a pre-veto state: deletes every game map (its
     * rounds/player stats/advanced stats cascade at the DB level, see
     * 0012_game_maps.php and friends) and the veto itself, so the operator
     * can redo the veto from scratch.
     */
    public function resetMaps(Request $request, Tournament $tournament, Matchs $match): RedirectResponse
    {
        $this->requireEditable($request, $tournament, $match, 'maps.reset');

        DB::transaction(function () use ($match) {
            $match->game_maps()->delete();
            $match->map_bans()->delete();
        });

        activity('tournament')->causedBy($request->user())
            ->performedOn($match)->log('match.maps_reset');

        return redirect()->route('admin.matches.show', [$tournament, $match])->with('status', 'match-maps-reset');
    }

    public function updateVeto(Request $request, Tournament $tournament, Matchs $match): RedirectResponse
    {
        $this->requireEditable($request, $tournament, $match, 'matches.veto.edit');

        $validated = $request->validate([
            'maps' => ['required', 'array'],
            'maps.*.map_name' => ['required', 'string', Rule::in(array_merge(['none'], self::MAP_POOL))],
            'maps.*.team' => ['required', 'string', Rule::in(['none', (string) $match->team_a_id, (string) $match->team_b_id])],
            'maps.*.type' => ['required', 'string', Rule::in(['none', 'ban', 'pick', 'decider'])],
            'maps.*.side' => ['nullable', 'string', Rule::in(['', 'atk', 'def'])],
            'maps.*.side_picked_by' => ['nullable', 'string'],
        ]);

        $vetos = [];

        foreach (array_values($validated['maps']) as $index => $row) {
            if ($row['map_name'] === 'none' || $row['team'] === 'none' || $row['type'] === 'none') {
                continue;
            }

            $vetos[] = [
                'team_id' => (int) $row['team'],
                'map_name' => $row['map_name'],
                'type' => $row['type'],
                'side' => ($row['side'] ?? null) ?: null,
                'side_picked_by' => ($row['side_picked_by'] ?? null) ? (int) $row['side_picked_by'] : null,
                'order' => $index + 1,
            ];
        }

        DB::transaction(function () use ($match, $vetos) {
            $match->map_bans()->delete();

            foreach ($vetos as $veto) {
                $match->map_bans()->create($veto);
            }

            $this->recomputeGameMaps($match, $vetos);
        });

        activity('tournament')->causedBy($request->user())
            ->performedOn($match)->log('match.veto_updated');

        return redirect()->route('admin.matches.show', [$tournament, $match])->with('status', 'match-veto-updated');
    }

    public function importWikicode(Request $request, Tournament $tournament, Matchs $match): RedirectResponse
    {
        $this->requireEditable($request, $tournament, $match, 'matches.import');

        $validated = $request->validate(['wikicode' => ['required', 'string', 'max:100000']]);

        if (! $match->team_a_id || ! $match->team_b_id) {
            return back()->with('error', 'match-import-missing-teams');
        }

        $wikicode = $validated['wikicode'];
        $vetoRows = $this->parseWikicodeVeto($wikicode, $match->team_a_id, $match->team_b_id);

        foreach ($vetoRows as $row) {
            if (! in_array($row['map_name'], self::MAP_POOL, true)) {
                return back()->with('error', 'match-import-unknown-map')->withInput();
            }
        }

        if (empty($vetoRows)) {
            return back()->with('error', 'match-import-empty')->withInput();
        }

        $vetoPayload = [];
        foreach (array_values($vetoRows) as $index => $row) {
            $vetoPayload[] = [
                'team_id' => $row['team_id'],
                'map_name' => $row['map_name'],
                'type' => $row['type'],
                'side' => null,
                'side_picked_by' => $row['type'] !== 'ban' ? $row['team_id'] : null,
                'order' => $index + 1,
            ];
        }

        DB::transaction(function () use ($match, $vetoPayload) {
            $match->map_bans()->delete();

            foreach ($vetoPayload as $veto) {
                $match->map_bans()->create($veto);
            }

            $this->recomputeGameMaps($match, $vetoPayload);
        });

        $gameMaps = $match->game_maps()->orderBy('order')->get();

        foreach (range(1, 9) as $i) {
            $template = $this->extractWikicodeTemplate($wikicode, "map{$i}");

            if (! $template) {
                break;
            }

            $data = $this->parseWikicodeTemplateParams($template);
            $gameMap = $gameMaps->get($i - 1);

            if (! $gameMap) {
                continue;
            }

            if (($data['finished'] ?? '') === 'skip') {
                $gameMap->update(['team_a_score' => -1, 'team_b_score' => -1, 'is_completed' => true]);

                continue;
            }

            if (! empty($data['matchid'])) {
                $gameMap->update(['api_match_id' => $data['matchid']]);
            }
        }

        activity('tournament')->causedBy($request->user())
            ->performedOn($match)->log('match.wikicode_imported');

        return redirect()->route('admin.matches.edit', [$tournament, $match])->with('status', 'match-wikicode-imported');
    }

    /**
     * Recreate the match's game_maps from the veto's picked/decider maps,
     * preserving any existing map's stats/score when the map name is unchanged.
     */
    private function recomputeGameMaps(Matchs $match, array $vetos): void
    {
        $playedMaps = collect($vetos)
            ->filter(fn ($v) => in_array($v['type'], ['pick', 'decider']))
            ->sortBy('order')
            ->values();

        $existingMaps = $match->game_maps()->orderBy('order')->get()->keyBy('map_name');
        $keptIds = [];

        foreach ($playedMaps as $index => $veto) {
            $order = $index + 1;
            $existing = $existingMaps->get($veto['map_name']);

            if ($existing) {
                $existing->update(['order' => $order]);
                $keptIds[] = $existing->id;
            } else {
                $map = GameMap::create([
                    'match_id' => $match->id,
                    'map_name' => $veto['map_name'],
                    'order' => $order,
                    'is_completed' => false,
                ]);
                $keptIds[] = $map->id;
            }
        }

        $match->game_maps()->whereNotIn('id', $keptIds)->delete();
    }

    /**
     * Build the ordered list of veto rows (chronological ban/pick/decider order) from a Liquipedia
     * {{MapVeto}} template, attributing each row to the match's actual team A / team B ids.
     */
    private function parseWikicodeVeto(string $wikicode, int $teamAId, int $teamBId): array
    {
        $template = $this->extractWikicodeTemplate($wikicode, 'mapveto');

        if (! $template) {
            return [];
        }

        $params = $this->parseWikicodeTemplateParams($template);
        $types = array_filter(array_map('trim', explode(',', $params['types'] ?? '')));

        $rows = [];
        $n = 0;
        $lastTeam = null;

        foreach ($types as $type) {
            if ($type === 'decider') {
                $deciderMap = $params['decider'] ?? null;

                if ($deciderMap) {
                    $deciderTeam = $lastTeam === $teamAId ? $teamBId : $teamAId;
                    $rows[] = ['team_id' => $deciderTeam, 'map_name' => $deciderMap, 'type' => 'decider'];
                    $lastTeam = $deciderTeam;
                }

                continue;
            }

            $n++;
            $t1 = $params["t1map{$n}"] ?? '-';
            $t2 = $params["t2map{$n}"] ?? '-';

            if ($t1 !== '-' && $t1 !== '') {
                $rows[] = ['team_id' => $teamAId, 'map_name' => $t1, 'type' => $type];
                $lastTeam = $teamAId;
            }

            if ($t2 !== '-' && $t2 !== '') {
                $rows[] = ['team_id' => $teamBId, 'map_name' => $t2, 'type' => $type];
                $lastTeam = $teamBId;
            }
        }

        return $rows;
    }

    /**
     * Find the wikicode template assigned to `|paramName={{...}}` and return the full
     * `{{...}}` substring, respecting nested templates by tracking brace depth.
     */
    private function extractWikicodeTemplate(string $text, string $paramName): ?string
    {
        if (! preg_match('/\|\s*'.preg_quote($paramName, '/').'\s*=\s*(\{\{)/i', $text, $m, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $openPos = $m[1][1];
        $depth = 0;
        $len = strlen($text);
        $i = $openPos;

        while ($i < $len - 1) {
            if ($text[$i] === '{' && $text[$i + 1] === '{') {
                $depth++;
                $i += 2;

                continue;
            }

            if ($text[$i] === '}' && $text[$i + 1] === '}') {
                $depth--;
                $i += 2;

                if ($depth === 0) {
                    return substr($text, $openPos, $i - $openPos);
                }

                continue;
            }

            $i++;
        }

        return null;
    }

    private function parseWikicodeTemplateParams(string $template): array
    {
        $inner = trim(substr($template, 2, -2));
        $parts = explode('|', $inner);
        array_shift($parts);

        $params = [];

        foreach ($parts as $part) {
            $part = trim($part);

            if ($part === '') {
                continue;
            }

            [$key, $value] = array_pad(explode('=', $part, 2), 2, '');
            $params[trim($key)] = trim($value);
        }

        return $params;
    }

    private function validateMatch(Request $request, bool $isUpdate = false): array
    {
        $rule = $isUpdate ? 'sometimes' : 'required';

        $validated = $request->validate([
            'phase_id' => [$rule, 'integer', 'exists:tournament_phases,id'],
            'team_a_id' => ['sometimes', 'nullable', 'integer', 'exists:teams,id'],
            'team_b_id' => ['sometimes', 'nullable', 'integer', 'exists:teams,id'],
            'scheduled_at' => [$rule, 'date'],
            'status' => ['sometimes', 'string', 'in:upcoming,live,finished'],
            'team_a_score' => ['sometimes', 'nullable', 'integer'],
            'team_b_score' => ['sometimes', 'nullable', 'integer'],
            'best_of' => ['sometimes', 'nullable', 'integer'],
            'patch' => ['sometimes', 'nullable', 'string', 'max:20'],
            'match_order' => ['sometimes', 'nullable', 'integer'],
            'round_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'round_number' => ['sometimes', 'nullable', 'integer'],
        ]);

        if (array_key_exists('match_order', $validated) && $validated['match_order'] === null) {
            $validated['match_order'] = 0;
        }

        if (array_key_exists('round_number', $validated) && $validated['round_number'] === null) {
            $validated['round_number'] = 0;
        }

        if (array_key_exists('round_name', $validated) && $validated['round_name'] === null) {
            $validated['round_name'] = '';
        }

        return $validated;
    }

    /**
     * A match that is itself finished, or whose tournament is finished,
     * needs the `.finished` sibling of whichever permission is being
     * exercised (e.g. matches.edit.finished) — mirrors Dashboard's
     * admin-role-only reset-maps guard, but permission-based and granular
     * per action instead of one shared gate.
     */
    public static function isFinished(Tournament $tournament, Matchs $match): bool
    {
        return $match->status === 'finished' || $tournament->status === 'finished';
    }

    /**
     * Whether a finished match/tournament blocks the given base
     * permission for the current user (used by views to disable
     * individual buttons instead of the whole page).
     */
    public static function isLockedFor(Tournament $tournament, Matchs $match, string $permission): bool
    {
        return self::isFinished($tournament, $match) && ! auth()->user()->can("{$permission}.finished");
    }

    private function requireEditable(Request $request, Tournament $tournament, Matchs $match, string $permission): void
    {
        $this->ensureBelongsToTournament($tournament, $match);

        abort_unless(
            ! self::isFinished($tournament, $match) || $request->user()->can("{$permission}.finished"),
            403,
            "Only a user with {$permission}.finished can edit a finished match."
        );
    }

    /**
     * Route-model binding resolves {match} by id alone, independent of the
     * {tournament} segment in the URL — without this check a user could
     * address a match from a different (e.g. non-finished) tournament to
     * bypass the finished-tournament `.finished` permission gate above, or
     * otherwise act on a match outside the URL's stated tournament context.
     */
    private function ensureBelongsToTournament(Tournament $tournament, Matchs $match): void
    {
        abort_unless($match->tournament_id === $tournament->id, 404);
    }
}
