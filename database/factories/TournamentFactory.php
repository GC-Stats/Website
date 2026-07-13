<?php

namespace Database\Factories;

use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;

class TournamentFactory extends Factory
{
    protected $model = Tournament::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'region' => $this->faker->randomElement(['EMEA', 'Americas', 'Pacific', 'China']),
            'category' => $this->faker->randomElement(['Challengers', 'International', 'Game Changers']),
            'prize_pool' => $this->faker->randomElement(['$10,000', '$50,000', '$100,000']),
            'location' => $this->faker->city(),
            'start_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'end_date' => $this->faker->dateTimeBetween('now', '+1 month'),
            'status' => 'live',
            'description' => $this->faker->text(),
            'active' => true,
        ];
    }
}
