<?php

use App\Models\Player;
use App\Models\Team;
use App\Services\RosterService;
use Illuminate\Support\Facades\DB;

test('inserting a new active roster row closes out the player prior active team', function () {
    $player = Player::factory()->create();
    $oldTeam = Team::factory()->create();
    $newTeam = Team::factory()->create();

    $oldRowId = DB::table('player_team')->insertGetId([
        'player_id' => $player->id,
        'team_id' => $oldTeam->id,
        'role' => 'player',
        'joined_at' => '2025-01-01',
        'left_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    app(RosterService::class)->save('team_id', $newTeam->id, [
        [
            'player_id' => $player->id,
            'team_id' => $newTeam->id,
            'joined_at' => '2025-06-01',
            'left_at' => null,
        ],
    ]);

    $oldRow = DB::table('player_team')->where('id', $oldRowId)->first();

    expect($oldRow->left_at)->not->toBeNull()
        ->and(DB::table('player_team')->where('id', '!=', $oldRowId)->where('player_id', $player->id)->whereNull('left_at')->count())->toBe(1);
});

test('inserting a new row with an explicit left_at does not close other active rows', function () {
    $player = Player::factory()->create();
    $oldTeam = Team::factory()->create();
    $pastTeam = Team::factory()->create();

    $oldRowId = DB::table('player_team')->insertGetId([
        'player_id' => $player->id,
        'team_id' => $oldTeam->id,
        'role' => 'player',
        'joined_at' => '2025-01-01',
        'left_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    app(RosterService::class)->save('team_id', $pastTeam->id, [
        [
            'player_id' => $player->id,
            'team_id' => $pastTeam->id,
            'joined_at' => '2024-01-01',
            'left_at' => '2024-06-01',
        ],
    ]);

    $oldRow = DB::table('player_team')->where('id', $oldRowId)->first();

    expect($oldRow->left_at)->toBeNull();
});

test('rows not present in the entries list are deleted', function () {
    $team = Team::factory()->create();
    $player = Player::factory()->create();

    $rowId = DB::table('player_team')->insertGetId([
        'player_id' => $player->id,
        'team_id' => $team->id,
        'role' => 'player',
        'joined_at' => '2025-01-01',
        'left_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    app(RosterService::class)->save('team_id', $team->id, []);

    expect(DB::table('player_team')->where('id', $rowId)->exists())->toBeFalse();
});

test('deleteEntry removes the row and returns false for a missing id', function () {
    $team = Team::factory()->create();
    $player = Player::factory()->create();

    $rowId = DB::table('player_team')->insertGetId([
        'player_id' => $player->id,
        'team_id' => $team->id,
        'role' => 'player',
        'joined_at' => '2025-01-01',
        'left_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(app(RosterService::class)->deleteEntry($rowId))->toBeTrue()
        ->and(DB::table('player_team')->where('id', $rowId)->exists())->toBeFalse()
        ->and(app(RosterService::class)->deleteEntry($rowId))->toBeFalse();
});
