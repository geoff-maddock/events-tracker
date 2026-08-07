<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\SurveyCampaign;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Models\Visibility;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

class SurveyResponseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SurveyResponse::class;

    /**
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'survey_invitation_id' => null,
            'survey_campaign_id' => fn () => SurveyCampaign::factory()->create()->id,
            'user_id' => fn () => User::factory()->create()->id,
            'subject_type' => 'event',
            'subject_id' => fn () => Event::factory()->create()->id,
            'visibility_id' => Visibility::VISIBILITY_PRIVATE,
            'submitted_at' => now(),
        ];
    }

    public function about(Model $subject): static
    {
        return $this->state(fn () => [
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
        ]);
    }

    /**
     * The submitter opted to share this response publicly.
     */
    public function shared(): static
    {
        return $this->state(fn () => ['visibility_id' => Visibility::VISIBILITY_PUBLIC]);
    }
}
