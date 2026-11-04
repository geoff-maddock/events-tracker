<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserStatus;
use Hash;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'slug' => $this->faker->unique()->slug,
            'email' => $this->faker->unique()->safeEmail,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
            // Active by default: a random status made ~40% of users banned or
            // suspended, which CheckBanned (global middleware) turns into a
            // 401/redirect, so tests failed intermittently. Use the states
            // below for any other status.
            'user_status_id' => UserStatus::ACTIVE,
            'created_at' => now(),
            'updated_at' => now()
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['user_status_id' => UserStatus::PENDING]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['user_status_id' => UserStatus::SUSPENDED]);
    }

    public function banned(): static
    {
        return $this->state(fn () => ['user_status_id' => UserStatus::BANNED]);
    }

    public function deleted(): static
    {
        return $this->state(fn () => ['user_status_id' => UserStatus::DELETED]);
    }
}
