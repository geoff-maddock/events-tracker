<?php

namespace Database\Factories;

use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\EntityType;
use Illuminate\Database\Eloquent\Factories\Factory;

class EntityFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Entity::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'slug' => $this->faker->name,
            'short' => $this->faker->paragraph,
            'description' => $this->faker->paragraph,
            'entity_type_id' => function () {
                return EntityType::all()->random()->id;
            },
            'entity_status_id' => function () {
                return EntityStatus::all()->random()->id;
            },
            'facebook_username' => $this->faker->name,
            'twitter_username' => $this->faker->name,
            'created_by' => 1
        ];
    }

    /**
     * Indicate that the entity type is venue.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function venue()
    {
        return $this->state(function (array $attributes) {
            return [
                'entity_type_id' => EntityType::SPACE
            ];
        });
    }

    /**
     * Indicate that the entity type is promoter.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function promoter()
    {
        // TODO this might not be correct
        return $this->state(function (array $attributes) {
            return [
                'entity_type_id' => EntityType::INDIVIDUAL
            ];
        });
    }
}
