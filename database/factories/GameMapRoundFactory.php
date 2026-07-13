<?php

namespace Database\Factories;

use App\Models\GameMap;
use App\Models\GameMapRound;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameMapRoundFactory extends Factory
{
    protected $model = GameMapRound::class;

    public function definition(): array
    {
        return [
            'game_map_id' => GameMap::factory(),
            'round_number' => 1,
            'win_type' => 'Elimination',
        ];
    }
}
