<?php

namespace Database\Factories;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SocialAccount>
 */
class SocialAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => 'discord',
            'provider_id' => fake()->unique()->numerify('##################'),
            'nickname' => fake()->userName(),
            'avatar' => null,
            'token' => 'token',
            'refresh_token' => null,
            'token_expires_at' => null,
        ];
    }
}
