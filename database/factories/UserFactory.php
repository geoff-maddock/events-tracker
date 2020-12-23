<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserStatus;
use Hash;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'email' => $this->faker->unique()->safeEmail,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
            'user_status_id' => function () {
                return UserStatus::all()->random()->id;
            },
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
