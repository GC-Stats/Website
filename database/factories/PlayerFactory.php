<?php

namespace Database\Factories;

use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlayerFactory extends Factory
{
    protected $model = Player::class;

    public function definition(): array
    {
        return [
            'handle' => $this->faker->userName(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'country_code' => $this->faker->countryCode(),
            'bio' => $this->faker->paragraph(),
            'socials' => [
                'twitter' => 'https://twitter.com/'.$this->faker->userName(),
            ],
        ];
    }
}
