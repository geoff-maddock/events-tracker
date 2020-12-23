<?php

namespace Database\Factories;

use App\Models\Forum;
use App\Models\Thread;
use App\Models\ThreadCategory;
use App\Models\Visibility;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ThreadFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Thread::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $user = User::factory()->create();
        $forum = Forum::factory()->create();
        $threadCategory = ThreadCategory::factory()->count(3)->create();

        return [
            'forum_id' => function () {
                return Forum::all()->random()->id;
            },
            'thread_category_id' => function () {
                return ThreadCategory::all()->random()->id;
            },
            'name' => $this->faker->sentence,
            'slug' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'body' => $this->faker->paragraph,
            'allow_html' => $this->faker->boolean,
            'visibility_id' => function () {
                return Visibility::all()->random()->id;
            },
            'recipient_id' => null,
            'sort_order' => $this->faker->boolean,
            'is_edittable' => $this->faker->boolean,
            'likes' => random_int(0, 20),
            'views' => random_int(0, 100),
            'is_active' => $this->faker->boolean,
            'created_by' => function () {
                return User::all()->random()->id;
            },
            'updated_by' => function () {
                return User::all()->random()->id;
            },
            'created_at' => $this->faker->dateTimeBetween($startDate = '-1 years', $endDate = 'now', $timezone = null),
            'updated_at' => $this->faker->dateTimeBetween($startDate = '-1 months', $endDate = 'now', $timezone = null),
            'locked_by' => null,
            'locked_at' => null
        ];
    }
}
