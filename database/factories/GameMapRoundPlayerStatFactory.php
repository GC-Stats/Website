<?php

namespace Database\Factories;

use App\Models\GameMapRound;
use App\Models\GameMapRoundPlayerStat;
use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameMapRoundPlayerStatFactory extends Factory
{
    protected $model = GameMapRoundPlayerStat::class;

    public function definition(): array
    {
        return [
            'game_map_round_id' => GameMapRound::factory(),
            'player_id' => Player::factory(),
            'kills' => 0,
            'assists' => 0,
            'score' => 0,
            'economy_spent' => 2900,
            'economy_remaining' => 100,
            'weapon_id' => 'Phantom',
            'armor' => 'Heavy',
        ];
    }
}
