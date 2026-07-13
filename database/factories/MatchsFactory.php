<?php

namespace Database\Factories;

use App\Models\Matchs;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentPhase;
use Illuminate\Database\Eloquent\Factories\Factory;

class MatchsFactory extends Factory
{
    protected $model = Matchs::class;

    public function definition(): array
    {
        return [
            'tournament_id' => Tournament::factory(),
            'phase_id' => TournamentPhase::factory(),
            'team_a_id' => Team::factory(),
            'team_b_id' => Team::factory(),
            'scheduled_at' => $this->faker->dateTimeBetween('-1 week', '+1 week'),
            'status' => 'finished',
            'team_a_score' => $this->faker->numberBetween(0, 2),
            'team_b_score' => $this->faker->numberBetween(0, 2),
            'best_of' => 3,
        ];
    }
}
