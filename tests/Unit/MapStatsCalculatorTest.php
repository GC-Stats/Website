<?php

use App\Services\MapStatsCalculator;

beforeEach(function () {
    $this->calc = new MapStatsCalculator;
});

test('extractKills treats a bomb detonation as environmental, crediting no one', function () {
    $round = [
        'playerStats' => [
            [
                'puuid' => 'victim',
                'kills' => [
                    [
                        'victim' => 'victim',
                        'timeSinceRoundStartMillis' => 1000,
                        'finishingDamage' => ['damageType' => 'Bomb'],
                    ],
                ],
            ],
        ],
    ];

    $kills = $this->calc->extractKills($round);

    expect($kills)->toHaveCount(1)
        ->and($kills->first()['killer'])->toBeNull();
});

test('extractKills keeps a normal kill with its killer and assistants', function () {
    $round = [
        'playerStats' => [
            [
                'puuid' => 'attacker',
                'kills' => [
                    [
                        'killer' => 'attacker',
                        'victim' => 'victim',
                        'timeSinceRoundStartMillis' => 2500,
                        'assistants' => ['helper'],
                        'finishingDamage' => ['damageType' => 'Weapon', 'damageItem' => 'Vandal'],
                    ],
                ],
            ],
        ],
    ];

    $kills = $this->calc->extractKills($round);

    expect($kills->first())
        ->killer->toBe('attacker')
        ->victim->toBe('victim')
        ->assistants->toBe(['helper']);
});

test('firstHalfAttackerColor picks the color of the earliest first-half bomb planter', function () {
    $players = collect([
        ['puuid' => 'p1', 'teamId' => 'Red'],
        ['puuid' => 'p2', 'teamId' => 'Blue'],
    ]);

    $rounds = collect([
        ['roundNum' => 0, 'bombPlanter' => null],
        ['roundNum' => 1, 'bombPlanter' => 'p1'],
    ]);

    expect($this->calc->firstHalfAttackerColor($rounds, $players))->toBe('Red');
});

test('firstHalfAttackerColor returns null when no first-half round has a plant', function () {
    $players = collect([['puuid' => 'p1', 'teamId' => 'Red']]);
    $rounds = collect([['roundNum' => 0, 'bombPlanter' => null]]);

    expect($this->calc->firstHalfAttackerColor($rounds, $players))->toBeNull();
});

test('attackerColorForRoundIndex swaps sides at halftime and alternates in overtime', function () {
    expect($this->calc->attackerColorForRoundIndex(0, 'Red', 'Red', 'Blue'))->toBe('Red')
        ->and($this->calc->attackerColorForRoundIndex(12, 'Red', 'Red', 'Blue'))->toBe('Blue')
        ->and($this->calc->attackerColorForRoundIndex(24, 'Red', 'Red', 'Blue'))->toBe('Red')
        ->and($this->calc->attackerColorForRoundIndex(25, 'Red', 'Red', 'Blue'))->toBe('Blue');
});

test('applyTradeStats credits the avenger and marks the original victim as traded', function () {
    $agg = [
        'killer' => $this->calc->emptyAdvancedStatsRow(),
        'victim' => $this->calc->emptyAdvancedStatsRow(),
        'avenger' => $this->calc->emptyAdvancedStatsRow(),
    ];

    $teamByPuuid = collect(['killer' => 'Red', 'victim' => 'Blue', 'avenger' => 'Blue']);

    $kills = collect([
        ['killer' => 'killer', 'victim' => 'victim', 'time' => 1000, 'assistants' => []],
        ['killer' => 'avenger', 'victim' => 'killer', 'time' => 1500, 'assistants' => []],
    ]);

    $this->calc->applyTradeStats($agg, $kills, $teamByPuuid);

    expect($agg['victim']['traded_deaths'])->toBe(1)
        ->and($agg['avenger']['trade_kills'])->toBe(1)
        ->and($agg['killer']['trade_kills'])->toBe(0);
});

test('applyTradeStats does not count a trade outside the trade window', function () {
    $agg = [
        'killer' => $this->calc->emptyAdvancedStatsRow(),
        'victim' => $this->calc->emptyAdvancedStatsRow(),
        'avenger' => $this->calc->emptyAdvancedStatsRow(),
    ];

    $teamByPuuid = collect(['killer' => 'Red', 'victim' => 'Blue', 'avenger' => 'Blue']);

    $kills = collect([
        ['killer' => 'killer', 'victim' => 'victim', 'time' => 1000, 'assistants' => []],
        ['killer' => 'avenger', 'victim' => 'killer', 'time' => 1000 + MapStatsCalculator::TRADE_WINDOW_MS + 1, 'assistants' => []],
    ]);

    $this->calc->applyTradeStats($agg, $kills, $teamByPuuid);

    expect($agg['victim']['traded_deaths'])->toBe(0)
        ->and($agg['avenger']['trade_kills'])->toBe(0);
});

test('applyClutchStats records a 1v1 win for the last alive player of the winning team', function () {
    $agg = [
        'a1' => $this->calc->emptyAdvancedStatsRow(),
        'a2' => $this->calc->emptyAdvancedStatsRow(),
        'b1' => $this->calc->emptyAdvancedStatsRow(),
    ];

    $teamByPuuid = collect(['a1' => 'Red', 'a2' => 'Red', 'b1' => 'Blue']);
    $rosterByColor = collect(['Red' => ['a1', 'a2'], 'Blue' => ['b1']]);

    // b1 kills a2, leaving a1 alone against b1 (1v1); Red (a1's team) wins the round.
    $kills = collect([
        ['killer' => 'b1', 'victim' => 'a2', 'time' => 500, 'assistants' => []],
    ]);

    $this->calc->applyClutchStats($agg, $kills, $teamByPuuid, $rosterByColor, 'Red', 'Blue', 'Red');

    expect($agg['a1']['clutch_1v1_total'])->toBe(1)
        ->and($agg['a1']['clutch_1v1_won'])->toBe(1);
});
