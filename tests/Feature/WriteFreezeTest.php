<?php

use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\Support\PermissionTeam;
use App\Support\WriteFreeze;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    PermissionTeam::global();
});

afterEach(fn () => WriteFreeze::disable());

test('the command toggles the freeze', function () {
    $this->artisan('app:write-freeze', ['state' => 'on'])->assertSuccessful();
    expect(WriteFreeze::active())->toBeTrue();

    $this->artisan('app:write-freeze', ['state' => 'off'])->assertSuccessful();
    expect(WriteFreeze::active())->toBeFalse();

    $this->artisan('app:write-freeze', ['state' => 'nope'])->assertExitCode(2);
});

test('public write routes are refused while frozen', function () {
    $user = User::factory()->create();
    $player = Player::factory()->create();
    $team = Team::factory()->create();
    $other = User::factory()->create();

    WriteFreeze::enable();

    $this->actingAs($user)->get(route('forum.general.create'))->assertStatus(503);
    $this->actingAs($user)->post(route('forum.general.store'), ['title' => 'x', 'body' => 'x'])->assertStatus(503);
    $this->actingAs($user)->get(route('players.change-requests.create', $player->id))->assertStatus(503);
    $this->actingAs($user)->get(route('teams.change-requests.create', $team->id))->assertStatus(503);
    $this->actingAs($user)->post(route('users.report', $other), ['category' => 'other', 'reason' => 'x'])->assertStatus(503);
});

test('public write routes open again once unfrozen', function () {
    $user = User::factory()->create();
    $player = Player::factory()->create();

    WriteFreeze::enable();
    WriteFreeze::disable();

    $this->actingAs($user)->get(route('forum.general.create'))->assertOk();
    $this->actingAs($user)->get(route('players.change-requests.create', $player->id))->assertOk();
});

test('account settings stay writable while frozen', function () {
    $user = User::factory()->create();

    WriteFreeze::enable();

    $this->actingAs($user)->put(route('account.bio.update'), ['bio' => 'hello'])->assertStatus(302);
});
