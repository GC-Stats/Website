<?php

/**
 * GC-Stats — Admin: tournament team roster picker
 *
 * Wraps the generic entity-picker (type=team) so tournament teams can be
 * added/removed live, without a full page reload. entity-picker updates
 * its own chip list optimistically as soon as select()/remove() runs, then
 * dispatches a per-instance event (see its `selectEventName`/
 * `removeEventName` props) that this component listens for to persist the
 * change. Afterwards $version is bumped, which changes the entity-picker's
 * wire:key and forces Livewire to remount it from the true DB state — this
 * matters because an attach can fail (team already registered), and we
 * don't want the optimistic chip to stick around when that happens.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 * @link      https://github.com/GC-Stats/Website
 */

use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentTeam;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new class extends Component {
    public Tournament $tournament;

    public int $tournamentId;

    public int $version = 0;

    public string $newTeamName = '';

    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    public function mount(Tournament $tournament): void
    {
        $this->tournament = $tournament;
        $this->tournamentId = $tournament->id;
    }

    #[On('tournament-team-select-{tournamentId}')]
    public function onSelect(int $id): void
    {
        $this->attach($id);
    }

    #[On('tournament-team-remove-{tournamentId}')]
    public function onRemove(int $id): void
    {
        $this->detach($id);
    }

    private function guardCanManage(): void
    {
        abort_unless(auth()->user()->can('tournaments.teams.manage'), 403);

        abort_unless(
            $this->tournament->active || auth()->user()->can('tournaments.inactive.teams.manage'),
            403,
            'Only a user with tournaments.inactive.teams.manage can manage teams on an inactive tournament.'
        );
    }

    private function attach(int $teamId): void
    {
        $this->resetMessages();
        $this->guardCanManage();

        $alreadyRegistered = TournamentTeam::where('tournament_id', $this->tournament->id)
            ->where('team_id', $teamId)
            ->exists();

        if ($alreadyRegistered) {
            $this->errorMessage = __('admin.status.tournament-team-already-registered');
            $this->version++;

            return;
        }

        TournamentTeam::create([
            'tournament_id' => $this->tournament->id,
            'team_id' => $teamId,
        ]);

        $this->tournament->touch();

        activity('tournament')->causedBy(auth()->user())
            ->performedOn($this->tournament)
            ->withProperties(['team_id' => $teamId])
            ->log('tournament.team_attached');

        $this->statusMessage = __('admin.status.tournament-team-attached');
        $this->version++;
    }

    private function detach(int $teamId): void
    {
        $this->resetMessages();
        $this->guardCanManage();

        TournamentTeam::where('tournament_id', $this->tournament->id)->where('team_id', $teamId)->delete();

        $this->tournament->touch();

        activity('tournament')->causedBy(auth()->user())
            ->performedOn($this->tournament)
            ->withProperties(['team_id' => $teamId])
            ->log('tournament.team_detached');

        $this->statusMessage = __('admin.status.tournament-team-detached');
        $this->version++;
    }

    public function quickCreate(): void
    {
        $this->resetMessages();
        $this->guardCanManage();

        $validated = $this->validate(['newTeamName' => ['required', 'string', 'max:100']]);

        $team = DB::transaction(function () use ($validated) {
            $team = Team::create(['name' => $validated['newTeamName'], 'socials' => []]);

            TournamentTeam::create([
                'tournament_id' => $this->tournament->id,
                'team_id' => $team->id,
            ]);

            return $team;
        });

        $this->tournament->touch();

        activity('tournament')->causedBy(auth()->user())
            ->performedOn($this->tournament)
            ->withProperties(['team_id' => $team->id, 'name' => $validated['newTeamName']])
            ->log('tournament.team_created_and_attached');

        $this->newTeamName = '';
        $this->statusMessage = __('admin.status.tournament-team-created');
        $this->version++;
    }

    private function resetMessages(): void
    {
        $this->statusMessage = null;
        $this->errorMessage = null;
    }

    public function with(): array
    {
        $teams = $this->tournament->teams()->orderBy('name')->get();

        return [
            'teams' => $teams,
            'teamIds' => $teams->pluck('id')->all(),
        ];
    }
}; ?>

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h2 class="text-xs font-black uppercase tracking-widest text-gc-yellow">
            {{ __('admin.tournaments.teams.title') }} ({{ count($teamIds) }})
        </h2>
    </div>

    @if ($statusMessage)
        <div class="bg-green-500/10 border border-green-500/30 text-green-400 text-xs rounded-lg px-4 py-3">
            {{ $statusMessage }}
        </div>
    @endif

    @if ($errorMessage)
        <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-xs rounded-lg px-4 py-3">
            {{ $errorMessage }}
        </div>
    @endif

    @can('tournaments.teams.manage')
        <form wire:submit="quickCreate" class="flex gap-2">
            <input type="text" wire:model="newTeamName" required
                   placeholder="{{ __('admin.tournaments.teams.create_name_label') }}"
                   class="flex-1 bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-gc-yellow transition">
            <button type="submit"
                    class="font-bold uppercase text-[10px] tracking-widest px-4 py-2.5 rounded-lg transition active:scale-95 bg-gc-yellow text-black hover:scale-105 hover:shadow-[0_0_20px_rgba(228,174,34,0.35)]">
                {{ __('admin.tournaments.teams.create_submit') }}
            </button>
        </form>
        @error('newTeamName')
            <p class="text-xs text-red-400">{{ $message }}</p>
        @enderror

        <livewire:entity-picker
            wire:key="tournament-team-picker-{{ $tournament->id }}-{{ $version }}"
            type="team"
            :name="'tournament_teams_'.$tournament->id"
            :placeholder="__('admin.teams.search_placeholder')"
            :multiple="true"
            :selected="$teamIds"
            :select-event-name="'tournament-team-select-'.$tournament->id"
            :remove-event-name="'tournament-team-remove-'.$tournament->id"
            :search-first="true"
            thumb-size="w-10 h-6"
        />
    @else
        <div class="space-y-2">
            @forelse ($teams as $team)
                <div class="flex items-center gap-2 bg-white/5 border border-white/10 rounded-lg px-4 py-3">
                    <img src="{{ $team->logo }}" alt="" class="w-6 h-6 object-contain rounded-lg shrink-0">
                    <p class="text-sm text-white font-semibold truncate">{{ $team->name }}</p>
                </div>
            @empty
                <p class="text-sm text-gray-500 italic text-center py-10">{{ __('admin.tournaments.teams.empty') }}</p>
            @endforelse
        </div>
    @endcan
</div>
