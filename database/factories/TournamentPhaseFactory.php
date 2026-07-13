<?php

namespace Database\Factories;

use App\Models\Tournament;
use App\Models\TournamentPhase;
use Illuminate\Database\Eloquent\Factories\Factory;

class TournamentPhaseFactory extends Factory
{
    protected $model = TournamentPhase::class;

    public function definition(): array
    {
        return [
            'tournament_id' => Tournament::factory(),
            'name' => 'Group Stage',
            'format' => 'gsl',
            'order' => 1,
        ];
    }
}
