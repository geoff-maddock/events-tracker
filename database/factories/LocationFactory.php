<?php

namespace Database\Factories;

use App\Models\Entity;
use App\Models\Location;
use App\Models\LocationType;
use App\Models\Visibility;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Location::class;

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
            'attn' => $this->faker->optional->name,
            'address_one' => $this->faker->streetAddress,
            'address_two' => $this->faker->optional->streetAddress,
            'city' => $this->faker->city,
            'state' => $this->faker->state,
            'postcode' => $this->faker->postcode,
            'location_type_id' => fn () => LocationType::query()->inRandomOrder()->value('id')
                ?? LocationType::factory(),
            'entity_id' => fn () => Entity::factory(),
            'capacity' => $this->faker->numberBetween(0, 100),
            'map_url' => $this->faker->optional->url,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'created_by' => fn () => User::factory(),
            'updated_by' => fn () => User::factory(),
            'visibility_id' => fn () => Visibility::query()->inRandomOrder()->value('id')
                ?? Visibility::factory(),
        ];
    }
}
