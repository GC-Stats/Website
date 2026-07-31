<?php

use App\Models\GameMap;
use App\Models\GameMapRound;
use App\Models\GameMapRoundPlayerPosition;
use App\Models\GameMapRoundPlayerStat;
use App\Models\GamePlayerStat;
use App\Models\Matchs;
use App\Models\Player;
use App\Models\Team;
use App\Services\HeatmapService;
use Carbon\Carbon;

function makeRound(array $mapAttributes = [], array $roundAttributes = []): GameMapRound
{
    $teamA = Team::factory()->create();
    $teamB = Team::factory()->create();

    $match = Matchs::factory()->create([
        'team_a_id' => $teamA->id,
        'team_b_id' => $teamB->id,
        'scheduled_at' => '2026-06-01 12:00:00',
    ]);

    $gameMap = GameMap::factory()->create(array_merge([
        'match_id' => $match->id,
        'map_name' => 'Ascent',
    ], $mapAttributes));

    return GameMapRound::factory()->create(array_merge([
        'game_map_id' => $gameMap->id,
        'atk_team' => $teamA->id,
        'def_team' => $teamB->id,
        'winning_team' => $teamA->id,
    ], $roundAttributes));
}

test('positions are normalized using the map calibration formula', function () {
    $round = makeRound();
    $player = Player::factory()->create();

    GameMapRoundPlayerPosition::create([
        'game_map_round_id' => $round->id,
        'event_type' => 'kill',
        'player_id' => $player->id,
        'role' => 'killer',
        'x' => 1000,
        'y' => 2000,
    ]);

    $positions = app(HeatmapService::class)->positions('ascent');

    expect($positions)->toHaveCount(1);

    $calibration = config('valorant_minimaps.ascent');
    $expectedX = $calibration['x_multiplier'] * 2000 + $calibration['x_scalar'];
    $expectedY = $calibration['y_multiplier'] * 1000 + $calibration['y_scalar'];

    expect($positions[0]['x'])->toEqualWithDelta($expectedX, 0.0000001);
    expect($positions[0]['y'])->toEqualWithDelta($expectedY, 0.0000001);
});

test('side filter narrows to attack or defense based on the round teams', function () {
    $round = makeRound();
    $atkPlayer = Player::factory()->create();
    $defPlayer = Player::factory()->create();

    GameMapRoundPlayerStat::create([
        'game_map_round_id' => $round->id,
        'player_id' => $atkPlayer->id,
        'team_id' => $round->atk_team,
        'kills' => 0, 'assists' => 0, 'score' => 0,
    ]);
    GameMapRoundPlayerStat::create([
        'game_map_round_id' => $round->id,
        'player_id' => $defPlayer->id,
        'team_id' => $round->def_team,
        'kills' => 0, 'assists' => 0, 'score' => 0,
    ]);

    GameMapRoundPlayerPosition::create([
        'game_map_round_id' => $round->id, 'event_type' => 'kill',
        'player_id' => $atkPlayer->id, 'role' => 'killer', 'x' => 100, 'y' => 100,
    ]);
    GameMapRoundPlayerPosition::create([
        'game_map_round_id' => $round->id, 'event_type' => 'kill',
        'player_id' => $defPlayer->id, 'role' => 'victim', 'x' => 200, 'y' => 200,
    ]);

    $service = app(HeatmapService::class);

    expect($service->positions('ascent'))->toHaveCount(2);
    expect($service->positions('ascent', side: 'atk'))->toHaveCount(1)
        ->and($service->positions('ascent', side: 'atk')[0]['player_id'])->toBe($atkPlayer->id);
    expect($service->positions('ascent', side: 'def'))->toHaveCount(1)
        ->and($service->positions('ascent', side: 'def')[0]['player_id'])->toBe($defPlayer->id);
});

test('team, player, and event type filters narrow the result set', function () {
    $round = makeRound();
    $playerA = Player::factory()->create();
    $playerB = Player::factory()->create();

    GameMapRoundPlayerStat::create([
        'game_map_round_id' => $round->id, 'player_id' => $playerA->id,
        'team_id' => $round->atk_team, 'kills' => 0, 'assists' => 0, 'score' => 0,
    ]);
    GameMapRoundPlayerStat::create([
        'game_map_round_id' => $round->id, 'player_id' => $playerB->id,
        'team_id' => $round->def_team, 'kills' => 0, 'assists' => 0, 'score' => 0,
    ]);

    GameMapRoundPlayerPosition::create([
        'game_map_round_id' => $round->id, 'event_type' => 'kill',
        'player_id' => $playerA->id, 'role' => 'killer', 'x' => 100, 'y' => 100,
    ]);
    GameMapRoundPlayerPosition::create([
        'game_map_round_id' => $round->id, 'event_type' => 'plant',
        'player_id' => $playerB->id, 'role' => 'planter', 'x' => 300, 'y' => 300,
    ]);

    $service = app(HeatmapService::class);

    expect($service->positions('ascent', teamId: $round->atk_team))->toHaveCount(1);
    expect($service->positions('ascent', playerId: $playerB->id))->toHaveCount(1);
    expect($service->positions('ascent', eventTypes: ['plant']))->toHaveCount(1)
        ->and($service->positions('ascent', eventTypes: ['plant'])[0]['event_type'])->toBe('plant');
});

test('agent filter narrows to positions from players on that agent for that map', function () {
    $round = makeRound();
    $playerA = Player::factory()->create();
    $playerB = Player::factory()->create();

    GamePlayerStat::create([
        'match_id' => $round->match_id, 'game_map_id' => $round->game_map_id, 'player_id' => $playerA->id,
        'team_id' => $round->atk_team, 'agent_name' => 'Jett',
        'kills' => 0, 'deaths' => 0, 'assists' => 0,
    ]);
    GamePlayerStat::create([
        'match_id' => $round->match_id, 'game_map_id' => $round->game_map_id, 'player_id' => $playerB->id,
        'team_id' => $round->def_team, 'agent_name' => 'Sova',
        'kills' => 0, 'deaths' => 0, 'assists' => 0,
    ]);

    GameMapRoundPlayerPosition::create([
        'game_map_round_id' => $round->id, 'event_type' => 'kill',
        'player_id' => $playerA->id, 'role' => 'killer', 'x' => 100, 'y' => 100,
    ]);
    GameMapRoundPlayerPosition::create([
        'game_map_round_id' => $round->id, 'event_type' => 'kill',
        'player_id' => $playerB->id, 'role' => 'killer', 'x' => 200, 'y' => 200,
    ]);

    $service = app(HeatmapService::class);

    expect($service->positions('ascent'))->toHaveCount(2);
    expect($service->positions('ascent', agent: 'Jett'))->toHaveCount(1)
        ->and($service->positions('ascent', agent: 'Jett')[0]['player_id'])->toBe($playerA->id);
    expect($service->positions('ascent', agent: 'Sova'))->toHaveCount(1)
        ->and($service->positions('ascent', agent: 'Sova')[0]['player_id'])->toBe($playerB->id);
});

test('time range filters by the round-clock time_ms', function () {
    $round = makeRound();
    $player = Player::factory()->create();

    GameMapRoundPlayerPosition::create([
        'game_map_round_id' => $round->id, 'event_type' => 'kill',
        'player_id' => $player->id, 'role' => 'killer', 'x' => 100, 'y' => 100,
        'time_ms' => 5000,
    ]);
    GameMapRoundPlayerPosition::create([
        'game_map_round_id' => $round->id, 'event_type' => 'kill',
        'player_id' => $player->id, 'role' => 'killer', 'x' => 200, 'y' => 200,
        'time_ms' => 45000,
    ]);

    $service = app(HeatmapService::class);

    expect($service->positions('ascent'))->toHaveCount(2);
    expect($service->positions('ascent', timeStart: 0, timeEnd: 20))->toHaveCount(1)
        ->and($service->positions('ascent', timeStart: 0, timeEnd: 20)[0]['x'])->not->toBeNull();
    expect($service->positions('ascent', timeStart: 40, timeEnd: 60))->toHaveCount(1);
    expect($service->positions('ascent', timeStart: 100))->toHaveCount(0);
});

test('date range filters by the match scheduled_at', function () {
    $round = makeRound();
    $player = Player::factory()->create();

    GameMapRoundPlayerPosition::create([
        'game_map_round_id' => $round->id, 'event_type' => 'kill',
        'player_id' => $player->id, 'role' => 'killer', 'x' => 100, 'y' => 100,
    ]);

    $service = app(HeatmapService::class);

    expect($service->positions('ascent', start: Carbon::parse('2026-05-01'), end: Carbon::parse('2026-05-31')))->toHaveCount(0);
    expect($service->positions('ascent', start: Carbon::parse('2026-06-01'), end: Carbon::parse('2026-06-30')))->toHaveCount(1);
});

test('an unknown map returns no positions instead of erroring', function () {
    expect(app(HeatmapService::class)->positions('not-a-real-map'))->toBe([]);
});

test('the heatmap widget route validates the map, agent, and color parameters', function () {
    $this->get('/widget/heatmap')->assertSessionHasErrors('map');
    $this->get('/widget/heatmap?map=not-a-real-map')->assertSessionHasErrors('map');
    $this->get('/widget/heatmap?map=ascent&agent=NotAnAgent')->assertSessionHasErrors('agent');
    $this->get('/widget/heatmap?map=ascent&color=not-a-hex')->assertSessionHasErrors('color');
    $this->get('/widget/heatmap?map=ascent&agent=Jett&color=ff0000')->assertStatus(200);
    $this->get('/widget/heatmap?map=ascent&color=%23ff0000')->assertStatus(200);
    $this->get('/widget/heatmap?map=ascent&time_start=60&time_end=10')->assertSessionHasErrors('time_end');
    $this->get('/widget/heatmap?map=ascent&time_start=10&time_end=60')->assertStatus(200);
});

test('the heatmap widget page title is not inherited from the head-to-head widget', function () {
    $this->get('/widget/heatmap?map=ascent')
        ->assertDontSee(__('head_to_head.title'), false)
        ->assertSee(__('widgets.available.heatmap.name'), false);
});
