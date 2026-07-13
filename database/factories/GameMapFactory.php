<?php

namespace Database\Factories;

use App\Models\GameMap;
use App\Models\Matchs;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameMapFactory extends Factory
{
    protected $model = GameMap::class;

    public function definition(): array
    {
        return [
            'match_id' => Matchs::factory(),
            'map_name' => $this->faker->randomElement(['Ascent', 'Bind', 'Haven', 'Split', 'Icebox', 'Breeze', 'Fracture', 'Pearl', 'Lotus', 'Sunset']),
            'team_a_score' => 13,
            'team_b_score' => $this->faker->numberBetween(0, 11),
            'order' => 1,
            'is_completed' => true,
        ];
    }
}
