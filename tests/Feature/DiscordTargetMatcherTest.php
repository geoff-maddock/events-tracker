<?php

namespace Tests\Feature;

use App\Models\DiscordTarget;
use App\Models\DiscordTargetCriterion;
use App\Models\Entity;
use App\Models\Event;
use App\Models\Photo;
use App\Models\Tag;
use App\Models\Visibility;
use App\Services\Integrations\Discord\DiscordTargetMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Which events reach which Discord channels (issue #2058).
 *
 * The fail-safe cases matter as much as the matching ones: a half-configured
 * target must go quiet rather than firehose every event on the site into
 * someone's channel.
 */
class DiscordTargetMatcherTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function matcher(): DiscordTargetMatcher
    {
        return app(DiscordTargetMatcher::class);
    }

    /**
     * A public, future, flyered event — one that passes the base gate, so each
     * test isolates the criterion it is actually about.
     */
    private function eligibleEvent(array $attributes = []): Event
    {
        $event = Event::factory()->create(array_merge([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => now()->addDays(10),
            'cancelled_at' => null,
            'do_not_repost' => false,
        ], $attributes));

        $event->photos()->attach(Photo::factory()->create(['is_primary' => 1])->id);

        return $event->fresh();
    }

    private function targetWith(string $type, int $id, bool $exclude = false): DiscordTarget
    {
        $target = DiscordTarget::factory()->create();

        DiscordTargetCriterion::factory()->ofType($type, $id)->create([
            'discord_target_id' => $target->id,
            'mode' => $exclude
                ? DiscordTargetCriterion::MODE_EXCLUDE
                : DiscordTargetCriterion::MODE_INCLUDE,
        ]);

        return $target->fresh();
    }

    public function test_a_target_with_no_criteria_matches_nothing(): void
    {
        $event = $this->eligibleEvent();
        $target = DiscordTarget::factory()->create(['match_all' => false]);

        // Fail-safe: a half-configured channel goes quiet rather than
        // receiving every event on the site.
        $this->assertFalse($this->matcher()->accepts($target, $event));
    }

    public function test_match_all_receives_every_eligible_event(): void
    {
        $event = $this->eligibleEvent();
        $target = DiscordTarget::factory()->matchAll()->create();

        $this->assertTrue($this->matcher()->accepts($target, $event));
    }

    public function test_a_tag_criterion_matches_only_tagged_events(): void
    {
        $tag = Tag::factory()->create();
        $target = $this->targetWith(DiscordTargetCriterion::TYPE_TAG, $tag->id);

        $tagged = $this->eligibleEvent();
        $tagged->tags()->attach($tag->id);

        $untagged = $this->eligibleEvent();

        $this->assertTrue($this->matcher()->accepts($target, $tagged->fresh()));
        $this->assertFalse($this->matcher()->accepts($target, $untagged));
    }

    public function test_includes_within_one_type_are_ord(): void
    {
        $techno = Tag::factory()->create();
        $ambient = Tag::factory()->create();

        $target = DiscordTarget::factory()->create();
        foreach ([$techno->id, $ambient->id] as $tagId) {
            DiscordTargetCriterion::factory()->ofType(DiscordTargetCriterion::TYPE_TAG, $tagId)
                ->create(['discord_target_id' => $target->id]);
        }

        $event = $this->eligibleEvent();
        $event->tags()->attach($ambient->id);

        $this->assertTrue($this->matcher()->accepts($target->fresh(), $event->fresh()));
    }

    public function test_includes_across_types_are_anded(): void
    {
        $tag = Tag::factory()->create();
        $venue = Entity::factory()->venue()->create();

        $target = DiscordTarget::factory()->create();
        DiscordTargetCriterion::factory()->ofType(DiscordTargetCriterion::TYPE_TAG, $tag->id)
            ->create(['discord_target_id' => $target->id]);
        DiscordTargetCriterion::factory()->ofType(DiscordTargetCriterion::TYPE_VENUE, $venue->id)
            ->create(['discord_target_id' => $target->id]);

        $both = $this->eligibleEvent(['venue_id' => $venue->id]);
        $both->tags()->attach($tag->id);

        $tagOnly = $this->eligibleEvent();
        $tagOnly->tags()->attach($tag->id);

        $venueOnly = $this->eligibleEvent(['venue_id' => $venue->id]);

        $target = $target->fresh();
        $this->assertTrue($this->matcher()->accepts($target, $both->fresh()));
        $this->assertFalse($this->matcher()->accepts($target, $tagOnly->fresh()));
        $this->assertFalse($this->matcher()->accepts($target, $venueOnly));
    }

    public function test_an_exclude_vetoes_an_otherwise_matching_event(): void
    {
        $wanted = Tag::factory()->create();
        $unwanted = Tag::factory()->create();

        $target = DiscordTarget::factory()->create();
        DiscordTargetCriterion::factory()->ofType(DiscordTargetCriterion::TYPE_TAG, $wanted->id)
            ->create(['discord_target_id' => $target->id]);
        DiscordTargetCriterion::factory()->ofType(DiscordTargetCriterion::TYPE_TAG, $unwanted->id)
            ->exclude()->create(['discord_target_id' => $target->id]);

        $event = $this->eligibleEvent();
        $event->tags()->attach([$wanted->id, $unwanted->id]);

        $this->assertFalse($this->matcher()->accepts($target->fresh(), $event->fresh()));
    }

    public function test_excluding_a_venue_still_admits_events_with_no_venue(): void
    {
        $venue = Entity::factory()->venue()->create();

        $target = DiscordTarget::factory()->matchAll()->create();
        DiscordTargetCriterion::factory()->ofType(DiscordTargetCriterion::TYPE_VENUE, $venue->id)
            ->exclude()->create(['discord_target_id' => $target->id]);

        $atVenue = $this->eligibleEvent(['venue_id' => $venue->id]);
        $noVenue = $this->eligibleEvent(['venue_id' => null]);

        $target = $target->fresh();
        // An event with no venue is not "at the venue we are filtering out".
        $this->assertFalse($this->matcher()->accepts($target, $atVenue));
        $this->assertTrue($this->matcher()->accepts($target, $noVenue));
    }

    public function test_the_base_gate_rejects_ineligible_events(): void
    {
        $target = DiscordTarget::factory()->matchAll()->create();
        $matcher = $this->matcher();

        $this->assertFalse($matcher->accepts($target, $this->eligibleEvent([
            'visibility_id' => Visibility::VISIBILITY_PRIVATE,
        ])), 'a private event must not be broadcast');

        $this->assertFalse($matcher->accepts($target, $this->eligibleEvent([
            'start_at' => now()->subDays(3),
        ])), 'a past event must not be announced');

        $this->assertFalse($matcher->accepts($target, $this->eligibleEvent([
            'cancelled_at' => now(),
        ])), 'a cancelled event must not be announced');

        $this->assertFalse($matcher->accepts($target, $this->eligibleEvent([
            'do_not_repost' => true,
        ])), 'do_not_repost must be honoured');
    }

    public function test_requires_photo_excludes_events_without_a_flyer(): void
    {
        $flyerless = Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => now()->addDays(5),
            'do_not_repost' => false,
        ]);

        $strict = DiscordTarget::factory()->matchAll()->create(['requires_photo' => true]);
        $relaxed = DiscordTarget::factory()->matchAll()->withoutPhotoRequirement()->create();

        $this->assertFalse($this->matcher()->accepts($strict, $flyerless));
        $this->assertTrue($this->matcher()->accepts($relaxed, $flyerless));
    }

    public function test_targets_for_respects_the_posting_mode(): void
    {
        $event = $this->eligibleEvent();

        $onCreate = DiscordTarget::factory()->matchAll()->create([
            'post_on_create' => true, 'post_digest' => false,
        ]);
        DiscordTarget::factory()->matchAll()->create([
            'post_on_create' => false, 'post_digest' => true,
        ]);
        DiscordTarget::factory()->matchAll()->disabled()->create(['post_on_create' => true]);

        $matched = $this->matcher()->targetsFor($event, DiscordTarget::MODE_CREATE);

        $this->assertSame([$onCreate->id], $matched->pluck('id')->all());
    }

    public function test_events_for_windows_by_start_time(): void
    {
        $target = DiscordTarget::factory()->matchAll()->create();

        $inWindow = $this->eligibleEvent(['start_at' => now()->addDays(3)]);
        $this->eligibleEvent(['start_at' => now()->addDays(30)]);

        $found = $this->matcher()->eventsFor($target, now(), now()->addDays(7))->get();

        $this->assertSame([$inWindow->id], $found->pluck('id')->all());
    }

    public function test_both_directions_of_the_question_agree(): void
    {
        // accepts() and eventsFor() must never disagree — they are the same
        // SQL for exactly this reason.
        $tag = Tag::factory()->create();
        $target = $this->targetWith(DiscordTargetCriterion::TYPE_TAG, $tag->id);

        $matching = $this->eligibleEvent();
        $matching->tags()->attach($tag->id);
        $this->eligibleEvent();

        $listed = $this->matcher()->eventsFor($target)->pluck('id')->all();

        $this->assertSame([$matching->id], $listed);
        $this->assertTrue($this->matcher()->accepts($target, $matching->fresh()));
    }
}
