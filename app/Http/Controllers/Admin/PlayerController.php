<?php

/**
 * GC-Stats — Admin: players
 *
 * Profile/logo editing and deletion for players. Deliberately has no
 * owner/permission machinery — unlike Team, a Player has no per-entity
 * roles or ownership concept to manage. Gated by
 * `players.view`/`players.edit`/`players.delete`.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Exceptions\PlayerHasMatchesException;
use App\Http\Controllers\Public\Controller;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\Services\PlayerMergeService;
use App\Services\PlayerProfileService;
use App\Services\RosterService;
use App\Support\Activity\ActivityChangeSet;
use App\Support\Countries;
use App\Support\Pronouns;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PlayerController extends Controller
{
    /**
     * Correlated subquery for a player's most recent match date, via their
     * game_player_stats rows joined to matches. Mirrors
     * TeamController::latestMatchSubquery(), single-sided since a player
     * only ever appears on one side of a match.
     */
    private function latestMatchSubquery(): string
    {
        return '(SELECT MAX(m.scheduled_at) FROM game_player_stats gps '
            .'JOIN matches m ON m.id = gps.match_id '
            .'WHERE gps.player_id = players.id)';
    }

    private const ACTIVE_WITHIN_WINDOWS = [
        '30d' => '30 days',
        '90d' => '90 days',
        '6m' => '6 months',
        '1y' => '1 year',
    ];

    public function index(Request $request): View
    {
        $search = $request->get('q');
        $sort = $request->get('sort', 'name');
        $activeWithin = $request->get('active_within');

        $players = Player::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('handle', 'like', '%'.$this->escapeLike($search).'%');

                    if (ctype_digit($search)) {
                        $query->orWhere('id', (int) $search)->orWhere('vlr_id', (int) $search);
                    }
                });
            })
            ->when(
                $activeWithin && array_key_exists($activeWithin, self::ACTIVE_WITHIN_WINDOWS),
                fn ($query) => $query->whereRaw(
                    $this->latestMatchSubquery().' >= ?',
                    [now()->sub(self::ACTIVE_WITHIN_WINDOWS[$activeWithin])]
                )
            )
            ->when($sort === 'country', fn ($query) => $query->orderBy('country_code'))
            ->when($sort === 'recent_activity', fn ($query) => $query->orderByRaw($this->latestMatchSubquery().' DESC'))
            ->when($sort === 'name', fn ($query) => $query->orderBy('handle'))
            ->paginate(25)
            ->withQueryString();

        return view('admin.players.index', [
            'players' => $players,
            'search' => $search ?? '',
            'sort' => $sort,
            'activeWithin' => $activeWithin ?? '',
            'countries' => app(Countries::class)->list(),
            'teamOptions' => Team::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Quick creation from the admin players list — only the handle is
     * required. If a team is picked, the player joins its current roster
     * via RosterService (which closes out any prior active roster row,
     * though a brand new player never has one).
     */
    public function store(Request $request, RosterService $rosterService): RedirectResponse
    {
        $validated = $request->validate([
            'handle' => ['required', 'string', 'max:255'],
            'country_code' => ['nullable', 'string', 'max:3'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'vlr_id' => ['nullable', 'integer', 'max:99999999'],
        ]);

        $player = Player::create([
            'handle' => $validated['handle'],
            'country_code' => $validated['country_code'] ?? null,
            'vlr_id' => $validated['vlr_id'] ?? null,
            'is_active' => true,
        ]);

        if (! empty($validated['team_id'])) {
            $rosterService->addMember(Team::findOrFail($validated['team_id']), $player->id, 'player', now()->toDateString());
        }

        activity('player')->performedOn($player)->causedBy($request->user())
            ->withProperties(ActivityChangeSet::fromCreated($player, ['handle', 'country_code', 'vlr_id', 'is_active'])->toArray())
            ->log('player.created');

        return redirect()->route('admin.players.index')->with('status', 'player-created')->with('created_player', $player->id);
    }

    public function show(Request $request, Player $player, RosterService $rosterService): View
    {
        $userSearch = $request->get('user_q');
        $linkedUserIds = Player::whereNotNull('user_id')->where('id', '!=', $player->id)->pluck('user_id');

        $teamHistory = $rosterService->teamHistory($player->id);

        return view('admin.players.show', [
            'player' => $player,
            'countries' => app(Countries::class)->list(),
            'userSearch' => $userSearch ?? '',
            'userSearchResults' => $userSearch
                ? User::matching($userSearch)->whereNotIn('id', $linkedUserIds)->limit(10)->get()
                : collect(),
            'currentTeams' => $teamHistory->whereNull('left_at')->values(),
            'teamHistory' => $teamHistory->whereNotNull('left_at')->values(),
        ]);
    }

    public function updateAliases(Request $request, Player $player, PlayerProfileService $service): RedirectResponse
    {
        $validated = $request->validate([
            'aliases' => ['nullable', 'array'],
            'aliases.*' => ['nullable', 'string', 'max:255'],
        ]);

        $service->updateAliases($player, $validated['aliases'] ?? [], $request->user());

        return back()->with('status', 'aliases-updated');
    }

    public function syncTeamHistory(Request $request, Player $player, RosterService $rosterService): RedirectResponse
    {
        $validated = $request->validate([
            'entries' => ['array'],
            'entries.*.id' => ['nullable', 'integer', Rule::exists('player_team', 'id')->where('player_id', $player->id)],
            'entries.*.team_id' => ['required', 'integer', 'exists:teams,id'],
            'entries.*.role' => ['nullable', 'string', Rule::in(RosterService::ROLES)],
            'entries.*.joined_at' => ['required', 'date'],
            'entries.*.left_at' => ['nullable', 'date'],
        ]);

        $activeCount = collect($validated['entries'] ?? [])->filter(fn ($entry) => empty($entry['left_at']))->count();

        if ($activeCount > 1) {
            throw ValidationException::withMessages([
                'entries' => __('player.errors.multiple_active_teams'),
            ]);
        }

        $entries = collect($validated['entries'] ?? [])
            ->map(fn (array $entry) => [...$entry, 'player_id' => $player->id])
            ->all();

        $before = $this->teamHistorySnapshot($rosterService, $player);
        $rosterService->save('player_id', $player->id, $entries);
        $after = $this->teamHistorySnapshot($rosterService, $player);

        activity('player')->performedOn($player)->causedBy($request->user())
            ->withProperties(['player_id' => $player->id, 'changes' => $rosterService->diff($before, $after, 'team_name')])
            ->log('player.team_history.synced');

        return back()->with('status', 'team-history-synced');
    }

    private function teamHistorySnapshot(RosterService $rosterService, Player $player): array
    {
        return $rosterService->teamHistory($player->id)
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function linkUser(Request $request, Player $player, PlayerProfileService $service): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id', Rule::unique('players', 'user_id')->ignore($player->id)],
        ], [
            'user_id.unique' => __('admin.players.linked_user.already_linked'),
        ]);

        $service->linkUser($player, (int) $validated['user_id'], $request->user());

        return back()->with('status', 'user-linked');
    }

    public function unlinkUser(Request $request, Player $player, PlayerProfileService $service): RedirectResponse
    {
        $service->unlinkUser($player, $request->user());

        return back()->with('status', 'user-unlinked');
    }

    public function updateProfile(Request $request, Player $player, PlayerProfileService $service): RedirectResponse
    {
        $validated = $request->validate([
            'handle' => ['required', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'country_code' => ['nullable', 'string', 'max:3'],
            'pronouns' => ['required', 'integer', Rule::in(Pronouns::OPTIONS)],
            'bio' => ['nullable', 'string', 'max:2000'],
            'vlr_id' => ['nullable', 'integer', 'max:99999999'],
            'liquipedia_link' => ['nullable', 'url', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'socials' => ['nullable', 'array'],
            'socials.*' => ['nullable', 'string', 'max:255'],
        ]);

        $service->updateProfile($player, $validated, $request->user());

        return back()->with('status', 'profile-updated');
    }

    public function updateIdentifiers(Request $request, Player $player, PlayerProfileService $service): RedirectResponse
    {
        $validated = $request->validate([
            'val_id' => ['nullable', 'string', 'max:255', Rule::unique('players', 'val_id')->ignore($player->id)],
            'discord_id' => ['nullable', 'string', 'max:255', Rule::unique('players', 'discord_id')->ignore($player->id)],
        ], [
            'val_id.unique' => __('admin.players.identifiers.val_id_duplicate'),
            'discord_id.unique' => __('admin.players.identifiers.discord_id_duplicate'),
        ]);

        $service->updateIdentifiers($player, $validated, $request->user());

        return back()->with('status', 'identifiers-updated');
    }

    public function resetValId(Request $request, Player $player, PlayerProfileService $service): RedirectResponse
    {
        $service->resetValId($player, $request->user());

        return back()->with('status', 'val-id-reset');
    }

    public function resetDiscordId(Request $request, Player $player, PlayerProfileService $service): RedirectResponse
    {
        $service->resetDiscordId($player, $request->user());

        return back()->with('status', 'discord-id-reset');
    }

    public function updateLogo(Request $request, Player $player, PlayerProfileService $service): RedirectResponse
    {
        $validated = $request->validate([
            'logo' => ['required', 'file', 'image', 'max:10240'],
        ]);

        $service->updateLogo($player, $validated['logo'], $request->user());

        return back()->with('status', 'logo-updated');
    }

    public function storeLogoHistory(Request $request, Player $player, PlayerProfileService $service): RedirectResponse
    {
        $validated = $request->validate([
            'logo' => ['required', 'file', 'image', 'max:10240'],
            'from' => ['required', 'date'],
            'until' => ['required', 'date', 'after:from'],
            'is_visible' => ['nullable', 'boolean'],
        ]);

        $service->addLogoHistoryEntry($player, $validated['logo'], $validated['from'], $validated['until'], $request->user(), $request->boolean('is_visible'));

        return back()->with('status', 'logo-history-added');
    }

    public function updateLogoEntry(Request $request, Player $player, string $logo, PlayerProfileService $service): RedirectResponse
    {
        $validated = $request->validate([
            'from' => ['required', 'date'],
            'until' => ['nullable', 'date', 'after:from'],
            'is_visible' => ['nullable', 'boolean'],
        ]);

        $service->updateLogoEntry($player, $logo, $validated['from'], $validated['until'] ?? null, $request->user(), $request->boolean('is_visible'));

        return back()->with('status', 'logo-history-updated');
    }

    public function destroyLogoEntry(Request $request, Player $player, string $logo, PlayerProfileService $service): RedirectResponse
    {
        $service->deleteLogoEntry($player, $logo, $request->user());

        return back()->with('status', 'logo-history-removed');
    }

    public function destroy(Request $request, Player $player, PlayerMergeService $mergeService): RedirectResponse
    {
        try {
            $mergeService->delete($player, $request->user());
        } catch (PlayerHasMatchesException) {
            return redirect()->route('admin.players.show', $player)->with('error', 'player-delete-blocked');
        }

        return redirect()->route('admin.players.index')->with('status', 'player-deleted');
    }

    public function showMerge(Request $request, Player $player): View
    {
        $search = $request->get('q');

        return view('admin.players.merge', [
            'player' => $player,
            'search' => $search ?? '',
            'searchResults' => $search
                ? Player::where('id', '!=', $player->id)
                    ->where('handle', 'like', '%'.$this->escapeLike($search).'%')
                    ->limit(10)->get()
                : collect(),
            'teamItems' => DB::table('player_team')
                ->join('teams', 'teams.id', '=', 'player_team.team_id')
                ->where('player_team.player_id', $player->id)
                ->orderByDesc('player_team.joined_at')
                ->get(['player_team.id', 'teams.name as team_name', 'player_team.role', 'player_team.joined_at', 'player_team.left_at']),
            'newsItems' => $player->news()->orderByDesc('news.id')->get(['news.id', 'news.title']),
            'logoItems' => $player->logos()->orderByDesc('from')->get(),
            'matchGroups' => DB::table('game_player_stats')
                ->join('matches', 'matches.id', '=', 'game_player_stats.match_id')
                ->join('tournaments', 'tournaments.id', '=', 'game_player_stats.tournament_id')
                ->where('game_player_stats.player_id', $player->id)
                ->orderByDesc('matches.scheduled_at')
                ->get(['game_player_stats.id', 'game_player_stats.agent_name', 'tournaments.id as tournament_id', 'tournaments.name as tournament_name', 'matches.scheduled_at'])
                ->groupBy('tournament_id'),
        ]);
    }

    public function merge(Request $request, Player $player, PlayerMergeService $mergeService): RedirectResponse
    {
        $validated = $request->validate([
            'target_id' => ['required', 'integer', 'exists:players,id'],
            'teams' => ['array'],
            'teams.*' => ['integer'],
            'news' => ['array'],
            'news.*' => ['integer'],
            'logos' => ['array'],
            'logos.*' => ['string'],
            'stats' => ['array'],
            'stats.*' => ['integer'],
        ]);

        if ((int) $validated['target_id'] === $player->id) {
            throw ValidationException::withMessages(['target_id' => __('admin.players.merge.errors.same_player')]);
        }

        $target = Player::findOrFail($validated['target_id']);

        $mergeService->merge($player, $target, [
            'teams' => $validated['teams'] ?? [],
            'news' => $validated['news'] ?? [],
            'logos' => $validated['logos'] ?? [],
            'stats' => $validated['stats'] ?? [],
        ], $request->user());

        return redirect()->route('admin.players.show', $target)->with('status', 'player-merged');
    }
}
