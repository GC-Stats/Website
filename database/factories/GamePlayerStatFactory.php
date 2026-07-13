<?php

namespace Database\Factories;

use App\Models\GameMap;
use App\Models\GamePlayerStat;
use App\Models\Matchs;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class GamePlayerStatFactory extends Factory
{
    protected $model = GamePlayerStat::class;

    public function definition(): array
    {
        return [
            'match_id' => Matchs::factory(),
            'game_map_id' => GameMap::factory(),
            'player_id' => Player::factory(),
            'team_id' => Team::factory(),
            'agent_name' => $this->faker->randomElement(['Jett', 'Phoenix', 'Sova', 'Sage']),
            'kills' => $this->faker->numberBetween(10, 30),
            'deaths' => $this->faker->numberBetween(10, 30),
            'assists' => $this->faker->numberBetween(1, 15),
            'acs' => $this->faker->numberBetween(150, 350),
            'adr' => $this->faker->numberBetween(100, 200),
            'kast_percentage' => $this->faker->randomFloat(2, 0, 100),
            'first_kills' => $this->faker->numberBetween(0, 5),
            'first_deaths' => $this->faker->numberBetween(0, 5),
            'headshot_percentage' => $this->faker->randomFloat(2, 10, 40),
        ];
    }
}
