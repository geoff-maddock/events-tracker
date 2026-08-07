<?php

namespace Database\Factories;

use App\Models\SurveyCampaign;
use App\Models\Visibility;
use Illuminate\Database\Eloquent\Factories\Factory;

class SurveyCampaignFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SurveyCampaign::class;

    /**
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'slug' => $this->faker->unique()->slug(3),
            'name' => $this->faker->sentence(3),
            'intro' => $this->faker->sentence(),
            'subject_type' => 'event',
            'is_active' => true,
            'priority' => 0,
            'expires_after_days' => 30,
            'default_visibility_id' => Visibility::VISIBILITY_PRIVATE,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    /**
     * A campaign with no subject, e.g. a general interest poll.
     */
    public function subjectless(): static
    {
        return $this->state(fn () => ['subject_type' => null]);
    }
}
