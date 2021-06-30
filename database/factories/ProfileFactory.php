<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProfileFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Profile::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $user = User::factory()->create();

        return [
            'user_id' => function () {
                return User::all()->random()->id;
            },
            'first_name' => $this->faker->name,
            'last_name' => $this->faker->name,
            'default_theme' => 'light-theme',
            'bio' => $this->faker->paragraph,
            'location' => $this->faker->optional->city,
            'setting_weekly_update' => 1,
            'setting_daily_update' => 1,
            'setting_instant_update' => 1
        ];
    }
}
