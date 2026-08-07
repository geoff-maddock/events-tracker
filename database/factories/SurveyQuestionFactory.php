<?php

namespace Database\Factories;

use App\Models\SurveyCampaign;
use App\Models\SurveyQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

class SurveyQuestionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SurveyQuestion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'survey_campaign_id' => fn () => SurveyCampaign::factory()->create()->id,
            'key' => $this->faker->unique()->word(),
            'sort' => 0,
            'type' => SurveyQuestion::TYPE_TEXT,
            'prompt' => $this->faker->sentence(),
            'help' => null,
            'options' => null,
            'min_value' => null,
            'max_value' => null,
            'is_required' => false,
            'is_active' => true,
        ];
    }

    public function rating(int $min = 1, int $max = 5): static
    {
        return $this->state(fn () => [
            'type' => SurveyQuestion::TYPE_RATING,
            'min_value' => $min,
            'max_value' => $max,
        ]);
    }

    /**
     * @param  array<int, string>  $values
     */
    public function singleChoice(array $values = ['yes', 'no']): static
    {
        return $this->state(fn () => [
            'type' => SurveyQuestion::TYPE_SINGLE_CHOICE,
            'options' => array_map(
                static fn (string $value) => ['value' => $value, 'label' => ucfirst($value)],
                $values
            ),
        ]);
    }

    /**
     * @param  array<int, string>  $values
     */
    public function multiChoice(array $values = ['one', 'two', 'three']): static
    {
        return $this->state(fn () => [
            'type' => SurveyQuestion::TYPE_MULTI_CHOICE,
            'options' => array_map(
                static fn (string $value) => ['value' => $value, 'label' => ucfirst($value)],
                $values
            ),
        ]);
    }

    public function required(): static
    {
        return $this->state(fn () => ['is_required' => true]);
    }
}
