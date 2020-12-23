<?php

namespace Database\Factories;

use App\Models\Blog;
use App\Models\ContentType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class BlogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Blog::class;

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
            'body' => $this->faker->paragraph,
            'menu_id' => null,
            'content_type_id' => null,
            'visibility_id' => random_int(1, 3),
            'sort_order' => 0,
            'is_active' => $this->faker->boolean,
            'is_admin' => $this->faker->boolean,
            'allow_html' => $this->faker->boolean,
            'created_by' => random_int(1, 10),
            'updated_by' => random_int(1, 10),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ];
    }

    /**
     * Indicate that the blog context is plain text.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function plainText()
    {
        return $this->state(function (array $attributes) {
            return [
                'content_type_id' => ContentType::PLAIN_TEXT
            ];
        });
    }

    /**
     * Indicate that the blog context is plain text.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function html()
    {
        return $this->state(function (array $attributes) {
            return [
                'entity_type_id' => ContentType::HTML
            ];
        });
    }
}
