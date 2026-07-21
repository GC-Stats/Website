<?php

use App\Models\GameMap;
use App\Models\Matchs;
use App\Models\Tournament;
use App\Models\TournamentPhase;
use App\Models\User;
use App\Support\PermissionTeam;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    PermissionTeam::global();
});

function actingAsAdminWith(array $permissions): User
{
    $user = User::factory()->create();
    PermissionTeam::global();
    $user->givePermissionTo($permissions);

    return $user;
}

test('a match cannot be edited by addressing it through an unrelated tournament to dodge the finished-tournament lock', function () {
    $finishedTournament = Tournament::factory()->create(['status' => 'finished']);
    $otherTournament = Tournament::factory()->create(['status' => 'live']);

    $match = Matchs::factory()->create([
        'tournament_id' => $finishedTournament->id,
        'status' => 'upcoming',
    ]);

    // Holds matches.edit but NOT matches.edit.finished — should not be able
    // to edit a match that belongs to a finished tournament.
    $user = actingAsAdminWith(['matches.view', 'matches.edit']);

    $this->actingAs($user)
        ->put(route('admin.matches.update', [$otherTournament, $match]), [
            'status' => 'live',
        ])
        ->assertNotFound();

    expect($match->refresh()->status)->toBe('upcoming');
});

test('a match can still be edited normally through its own tournament', function () {
    $tournament = Tournament::factory()->create(['status' => 'live']);
    $match = Matchs::factory()->create([
        'tournament_id' => $tournament->id,
        'status' => 'upcoming',
    ]);

    $user = actingAsAdminWith(['matches.view', 'matches.edit']);

    $this->actingAs($user)
        ->put(route('admin.matches.update', [$tournament, $match]), [
            'status' => 'live',
        ])
        ->assertRedirect(route('admin.matches.show', [$tournament, $match]));

    expect($match->refresh()->status)->toBe('live');
});

test('show/edit/veto/destroy all 404 when the match does not belong to the URL tournament', function () {
    $tournamentA = Tournament::factory()->create(['status' => 'live']);
    $tournamentB = Tournament::factory()->create(['status' => 'live']);
    $match = Matchs::factory()->create(['tournament_id' => $tournamentA->id, 'status' => 'upcoming']);

    $user = actingAsAdminWith(['matches.view', 'matches.edit', 'matches.delete', 'matches.veto.edit']);

    $this->actingAs($user)->get(route('admin.matches.show', [$tournamentB, $match]))->assertNotFound();
    $this->actingAs($user)->get(route('admin.matches.edit', [$tournamentB, $match]))->assertNotFound();
    $this->actingAs($user)->get(route('admin.matches.veto.edit', [$tournamentB, $match]))->assertNotFound();
    $this->actingAs($user)->delete(route('admin.matches.destroy', [$tournamentB, $match]))->assertNotFound();

    expect(Matchs::find($match->id))->not->toBeNull();
});

test('a map cannot be reached through a match it does not belong to', function () {
    $tournament = Tournament::factory()->create(['status' => 'live']);
    $matchA = Matchs::factory()->create(['tournament_id' => $tournament->id, 'status' => 'upcoming']);
    $matchB = Matchs::factory()->create(['tournament_id' => $tournament->id, 'status' => 'upcoming']);
    $map = GameMap::factory()->create(['match_id' => $matchA->id, 'map_name' => 'Ascent']);

    $user = actingAsAdminWith(['matches.view', 'maps.edit', 'maps.delete']);

    $this->actingAs($user)
        ->get(route('admin.matches.maps.show', [$tournament, $matchB, $map]))
        ->assertNotFound();

    $this->actingAs($user)
        ->put(route('admin.matches.maps.update', [$tournament, $matchB, $map]), ['map_name' => 'Bind'])
        ->assertNotFound();

    expect($map->refresh()->map_name)->toBe('Ascent');
});

test('bulk-create rejects a phase belonging to another tournament and never leaves tournament_id unset', function () {
    $tournamentA = Tournament::factory()->create(['status' => 'live']);
    $tournamentB = Tournament::factory()->create(['status' => 'live']);
    $phaseOfB = TournamentPhase::factory()->create(['tournament_id' => $tournamentB->id]);

    $user = actingAsAdminWith(['operations.bulk-create']);

    $this->actingAs($user)
        ->post(route('admin.tournaments.operations.bulk-create', $tournamentA), [
            'phase_id' => $phaseOfB->id,
            'count' => 3,
            'scheduled_at' => now()->addWeek()->toDateTimeString(),
            'best_of' => 3,
        ])
        ->assertSessionHasErrors('phase_id');

    expect(Matchs::where('phase_id', $phaseOfB->id)->count())->toBe(0);
});

test('bulk-create sets tournament_id on every created match', function () {
    $tournament = Tournament::factory()->create(['status' => 'live']);
    $phase = TournamentPhase::factory()->create(['tournament_id' => $tournament->id]);

    $user = actingAsAdminWith(['operations.bulk-create']);

    $this->actingAs($user)
        ->post(route('admin.tournaments.operations.bulk-create', $tournament), [
            'phase_id' => $phase->id,
            'count' => 2,
            'scheduled_at' => now()->addWeek()->toDateTimeString(),
            'best_of' => 3,
        ])
        ->assertRedirect();

    $created = Matchs::where('phase_id', $phase->id)->get();
    expect($created)->toHaveCount(2);
    foreach ($created as $match) {
        expect($match->tournament_id)->toBe($tournament->id);
    }
});

test('cache-purge rejects a region outside the known allow-list', function () {
    $tournament = Tournament::factory()->create(['status' => 'live']);
    $user = actingAsAdminWith(['operations.cache-purge']);

    $this->actingAs($user)
        ->post(route('admin.tournaments.operations.cache-purge', $tournament), [
            'region' => '../admin',
            'api_match_id' => 'abc123',
        ])
        ->assertSessionHasErrors('region');
});

test('cache-purge accepts a whitelisted region', function () {
    Http::fake(['*' => Http::response(['status' => 'ok'], 200, ['X-Cache' => 'RENEWED'])]);

    $tournament = Tournament::factory()->create(['status' => 'live']);
    $user = actingAsAdminWith(['operations.cache-purge']);

    $this->actingAs($user)
        ->post(route('admin.tournaments.operations.cache-purge', $tournament), [
            'region' => 'na',
            'api_match_id' => 'abc123',
        ])
        ->assertSessionDoesntHaveErrors('region')
        ->assertRedirect();
});

test('wikicode import rejects an oversized payload', function () {
    $tournament = Tournament::factory()->create(['status' => 'live']);
    $match = Matchs::factory()->create([
        'tournament_id' => $tournament->id,
        'status' => 'upcoming',
    ]);

    $user = actingAsAdminWith(['matches.view', 'matches.import']);

    $this->actingAs($user)
        ->post(route('admin.matches.import-wikicode', [$tournament, $match]), [
            'wikicode' => str_repeat('a', 100001),
        ])
        ->assertSessionHasErrors('wikicode');
});
