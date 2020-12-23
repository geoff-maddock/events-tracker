<?php

namespace Database\Factories;

use App\Models\Menu;
use App\Models\Visibility;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class MenuFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Menu::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'slug' => $this->faker->slug,
            'body' => $this->faker->sentence,
            'menu_parent_id' => function () {
                return Menu::all()->random()->id;
            },
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'visibility_id' => function () {
                return Visibility::all()->random()->id;
            },
        ];
    }
}
