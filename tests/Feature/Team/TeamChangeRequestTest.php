<?php

use App\Models\ChangeRequest;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\Services\RosterService;

test('any authenticated user can reach a team\'s change-request page', function () {
    $team = Team::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('teams.change-requests.create', $team->id))
        ->assertOk();
});

test('proposing a profile change creates a pending ChangeRequest instead of updating the team', function () {
    $team = Team::factory()->create([
        'name' => 'Old Name',
        'short_name' => 'OLD',
        'country_code' => 'fr',
        'bio' => 'Old bio',
        'socials' => [],
    ]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('teams.change-requests.store', $team->id), [
            'name' => 'New Name',
            'short_name' => $team->short_name,
            'country_code' => $team->country_code,
            'bio' => $team->bio,
        ])
        ->assertRedirect();

    expect($team->fresh()->name)->toBe('Old Name');

    $request = ChangeRequest::first();
    expect($request)->not->toBeNull();
    expect($request->subject_type)->toBe('team');
    expect($request->subject_id)->toBe($team->id);
    expect($request->status)->toBe(ChangeRequest::STATUS_PENDING);

    $items = $request->items;
    expect($items)->toHaveCount(1);
    expect($items->first()->field)->toBe('name');
    expect($items->first()->new_value)->toBe('New Name');
});

test('vlr_id cannot be proposed through the change-request form', function () {
    $team = Team::factory()->create(['name' => 'Same Name', 'short_name' => 'SAME', 'country_code' => 'fr', 'bio' => 'Bio', 'socials' => [], 'vlr_id' => 123]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('teams.change-requests.store', $team->id), [
            'name' => $team->name,
            'short_name' => $team->short_name,
            'country_code' => $team->country_code,
            'bio' => $team->bio,
            'vlr_id' => 999,
        ])
        ->assertSessionHasErrors('change_request');

    expect(ChangeRequest::count())->toBe(0);
    expect($team->fresh()->vlr_id)->toBe(123);
});

test('proposing a new roster addition creates a pending roster_add item', function () {
    $team = Team::factory()->create(['name' => 'Same Name', 'short_name' => 'SAME', 'country_code' => 'fr', 'bio' => 'Bio', 'socials' => []]);
    $player = Player::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('teams.change-requests.store', $team->id), [
            'name' => $team->name,
            'short_name' => $team->short_name,
            'country_code' => $team->country_code,
            'bio' => $team->bio,
            'new_players' => [
                ['player_id' => $player->id, 'role' => 'player', 'joined_at' => '2025-01-01'],
            ],
        ])
        ->assertRedirect();

    $request = ChangeRequest::first();
    expect($request)->not->toBeNull();

    $item = $request->items->first();
    expect($item->field)->toBe('roster_add');
    expect($item->new_value)->toMatchArray(['player_id' => $player->id, 'role' => 'player', 'joined_at' => '2025-01-01']);

    // Never applied directly — the roster stays untouched until a staff member accepts it.
    expect($player->teams()->count())->toBe(0);
});

test('proposing to remove an active roster entry creates a roster_history item closing it out', function () {
    $team = Team::factory()->create(['name' => 'Same Name', 'short_name' => 'SAME', 'country_code' => 'fr', 'bio' => 'Bio', 'socials' => []]);
    $player = Player::factory()->create();
    $user = User::factory()->create();

    app(RosterService::class)->addMember($team, $player->id, 'player', '2025-01-01');
    $rowId = app(RosterService::class)->history($team->id)->first()->id;

    $this->actingAs($user)
        ->post(route('teams.change-requests.store', $team->id), [
            'name' => $team->name,
            'short_name' => $team->short_name,
            'country_code' => $team->country_code,
            'bio' => $team->bio,
            'roster' => [
                $rowId => ['removed' => '1'],
            ],
        ])
        ->assertRedirect();

    $item = ChangeRequest::first()->items->first();
    expect($item->field)->toBe('roster_history');
    expect($item->new_value['left_at'])->toBe(now()->toDateString());

    // Never applied directly — the player is still active on the roster until accepted.
    expect($player->fresh()->teams()->wherePivotNull('left_at')->count())->toBe(1);
});

test('submitting no changes returns a validation error instead of an empty ChangeRequest', function () {
    $team = Team::factory()->create([
        'name' => 'Same Name',
        'short_name' => 'SAME',
        'country_code' => 'fr',
        'bio' => 'Same bio',
        'socials' => [],
    ]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('teams.change-requests.store', $team->id), [
            'name' => $team->name,
            'short_name' => $team->short_name,
            'country_code' => $team->country_code,
            'bio' => $team->bio,
        ])
        ->assertSessionHasErrors('change_request');

    expect(ChangeRequest::count())->toBe(0);
});
