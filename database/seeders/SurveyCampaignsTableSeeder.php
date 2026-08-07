<?php

namespace Database\Seeders;

use App\Models\SurveyCampaign;
use App\Models\SurveyQuestion;
use App\Models\Visibility;
use Illuminate\Database\Seeder;

/**
 * Seeds the feedback survey campaigns and their questions (issue #1998).
 *
 * IMPORTANT — this seeder deliberately does NOT follow the pattern used by the
 * other lookup seeders in this directory. Those begin with
 * `DB::table('x')->delete()` and insert hardcoded ids; doing that here would
 * cascade-delete every invitation, response and answer on the next seed run.
 *
 * Instead everything is `updateOrCreate`, keyed on the campaign slug and on
 * (campaign, question key). Re-running is safe and preserves collected data.
 * Application code must reference campaigns by slug constant, never by id —
 * seeded ids are not stable across environments.
 */
class SurveyCampaignsTableSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPostEventAttendee();
    }

    /**
     * Post-event feedback for users who marked "Attending".
     *
     * Six questions, one required. The required one is a single tap, so an
     * abandoned survey still yields a usable rating. Q2 and Q3 share an option
     * vocabulary so the admin summary can show liked-vs-disliked per dimension.
     */
    private function seedPostEventAttendee(): void
    {
        $campaign = SurveyCampaign::updateOrCreate(
            ['slug' => SurveyCampaign::POST_EVENT_ATTENDEE],
            [
                'name' => 'Post-event feedback',
                'intro' => 'You said you were going to this one — how was it? Six quick questions, about a minute. Only the first is required.',
                'subject_type' => 'event',
                'is_active' => true,
                'priority' => 10,
                'expires_after_days' => 30,
                'default_visibility_id' => Visibility::VISIBILITY_PRIVATE,
            ]
        );

        // Shared vocabulary between "what worked" and "what didn't", so the
        // summary can line them up against each other.
        $dimensions = [
            ['value' => 'lineup', 'label' => 'The lineup / music'],
            ['value' => 'sound', 'label' => 'Sound & production'],
            ['value' => 'venue', 'label' => 'The venue & space'],
            ['value' => 'crowd', 'label' => 'The crowd & vibe'],
            ['value' => 'value', 'label' => 'Value for the price'],
            ['value' => 'organization', 'label' => 'Organization — timing, entry, staff'],
            ['value' => 'safety', 'label' => 'Felt safe & comfortable'],
        ];

        $questions = [
            [
                'key' => 'overall_rating',
                'sort' => 1,
                'type' => SurveyQuestion::TYPE_RATING,
                'prompt' => 'Overall, how was it?',
                'help' => null,
                'options' => null,
                'min_value' => 1,
                'max_value' => 5,
                'is_required' => true,
            ],
            [
                'key' => 'liked_most',
                'sort' => 2,
                'type' => SurveyQuestion::TYPE_MULTI_CHOICE,
                'prompt' => 'What worked? Pick anything that stood out.',
                'help' => null,
                'options' => $dimensions,
                'min_value' => null,
                'max_value' => null,
                'is_required' => false,
            ],
            [
                'key' => 'liked_least',
                'sort' => 3,
                'type' => SurveyQuestion::TYPE_MULTI_CHOICE,
                'prompt' => "What didn't? Be honest — this goes to the promoter and venue.",
                'help' => null,
                'options' => array_merge($dimensions, [
                    ['value' => 'nothing', 'label' => 'Nothing — it was great'],
                ]),
                'min_value' => null,
                'max_value' => null,
                'is_required' => false,
            ],
            [
                'key' => 'want_more',
                'sort' => 4,
                'type' => SurveyQuestion::TYPE_MULTI_CHOICE,
                'prompt' => 'What would make you more likely to come back?',
                'help' => null,
                'options' => [
                    ['value' => 'more_genre', 'label' => 'More of this kind of music'],
                    ['value' => 'bigger_lineup', 'label' => 'A bigger or different lineup'],
                    ['value' => 'more_locals', 'label' => 'More local openers'],
                    ['value' => 'better_sound', 'label' => 'Better sound'],
                    ['value' => 'earlier_start', 'label' => 'Earlier start'],
                    ['value' => 'later_end', 'label' => 'Later end'],
                    ['value' => 'cheaper', 'label' => 'Cheaper entry'],
                    ['value' => 'diff_venue', 'label' => 'A different venue'],
                    ['value' => 'more_space', 'label' => 'More room / seating'],
                ],
                'min_value' => null,
                'max_value' => null,
                'is_required' => false,
            ],
            [
                'key' => 'return_likelihood',
                'sort' => 5,
                'type' => SurveyQuestion::TYPE_SINGLE_CHOICE,
                'prompt' => 'Would you go to another event by this promoter?',
                'help' => null,
                'options' => [
                    ['value' => 'definitely', 'label' => 'Definitely'],
                    ['value' => 'probably', 'label' => 'Probably'],
                    ['value' => 'maybe', 'label' => 'Maybe'],
                    ['value' => 'no', 'label' => 'Probably not'],
                ],
                'min_value' => null,
                'max_value' => null,
                'is_required' => false,
            ],
            [
                'key' => 'comments',
                'sort' => 6,
                'type' => SurveyQuestion::TYPE_TEXT,
                'prompt' => "Anything else you'd want the promoter or venue to know?",
                'help' => 'One specific thing is more useful than a general vibe.',
                'options' => null,
                'min_value' => null,
                'max_value' => null,
                'is_required' => false,
            ],
        ];

        foreach ($questions as $question) {
            SurveyQuestion::updateOrCreate(
                [
                    'survey_campaign_id' => $campaign->id,
                    'key' => $question['key'],
                ],
                array_merge($question, ['is_active' => true])
            );
        }

        SurveyCampaign::forgetCached(SurveyCampaign::POST_EVENT_ATTENDEE);
    }
}
