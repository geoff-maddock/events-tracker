<?php

namespace Tests\Feature;

use App\Models\DiscordTarget;
use App\Models\DiscordTargetCriterion;
use App\Models\Entity;
use App\Models\Event;
use App\Models\Series;
use App\Models\Tag;
use App\Models\Visibility;
use App\Services\Integrations\Discord\DiscordTargetMatcher;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Deleting a tag, entity or series removes the Discord target criteria that
 * pointed at it, and never widens a target in doing so (#2240).
 */
class DiscordCriteriaCleanupTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function target(): DiscordTarget
    {
        return DiscordTarget::factory()->create(['is_enabled' => true, 'match_all' => false]);
    }

    private function criterion(DiscordTarget $target, string $type, int $id, string $mode = DiscordTargetCriterion::MODE_INCLUDE): DiscordTargetCriterion
    {
        return DiscordTargetCriterion::factory()->create([
            'discord_target_id' => $target->id, 'criteria_type' => $type, 'criteria_id' => $id, 'mode' => $mode,
        ]);
    }

    public function test_a_deleted_tags_criteria_go_and_a_target_still_scoped_by_that_type_stays_on(): void
    {
        [$gone, $kept] = Tag::factory()->count(2)->create();
        $target = $this->target();
        $this->criterion($target, DiscordTargetCriterion::TYPE_TAG, $gone->id);
        $this->criterion($target, DiscordTargetCriterion::TYPE_TAG, $kept->id);

        $gone->delete();

        $this->assertSame([$kept->id], $target->criteria()->pluck('criteria_id')->all());
        $this->assertTrue($target->fresh()->is_enabled);
    }

    public function test_losing_the_last_include_of_a_type_disables_the_target_instead_of_widening_it(): void
    {
        $tag = Tag::factory()->create();
        $venue = Entity::factory()->create();
        $target = $this->target();
        $this->criterion($target, DiscordTargetCriterion::TYPE_TAG, $tag->id);
        $this->criterion($target, DiscordTargetCriterion::TYPE_VENUE, $venue->id);

        // an untagged public event at the venue: the target must not start matching it
        $event = Event::factory()->create(['venue_id' => $venue->id, 'visibility_id' => Visibility::VISIBILITY_PUBLIC, 'start_at' => Carbon::now()->addDay()]);

        $tag->delete();

        $target->refresh();
        $this->assertFalse($target->is_enabled);
        $this->assertSame([DiscordTargetCriterion::TYPE_VENUE], $target->criteria()->pluck('criteria_type')->all());
        // the remaining venue filter alone would match the event, which is why the target is off
        $this->assertTrue(app(DiscordTargetMatcher::class)->applyCriteria(Event::query(), $target)->whereKey($event->id)->exists());
    }

    public function test_an_exclude_is_removed_without_touching_the_target(): void
    {
        $tag = Tag::factory()->create();
        $target = $this->target();
        $this->criterion($target, DiscordTargetCriterion::TYPE_TAG, $tag->id, DiscordTargetCriterion::MODE_EXCLUDE);

        $tag->delete();

        $this->assertSame(0, $target->criteria()->count());
        $this->assertTrue($target->fresh()->is_enabled);
    }

    public function test_an_entitys_criteria_go_whichever_role_they_used(): void
    {
        $entity = Entity::factory()->create();
        $other = Entity::factory()->create();
        $target = $this->target();
        foreach ([DiscordTargetCriterion::TYPE_ENTITY, DiscordTargetCriterion::TYPE_VENUE, DiscordTargetCriterion::TYPE_PROMOTER] as $type) {
            $this->criterion($target, $type, $entity->id);
            $this->criterion($target, $type, $other->id);
        }

        $entity->delete();

        $this->assertSame([$other->id], $target->criteria()->pluck('criteria_id')->unique()->values()->all());
        $this->assertSame(3, $target->criteria()->count());
        $this->assertTrue($target->fresh()->is_enabled);
    }

    public function test_a_deleted_series_criteria_go(): void
    {
        $series = Series::factory()->create();
        $target = $this->target();
        $this->criterion($target, DiscordTargetCriterion::TYPE_SERIES, $series->id, DiscordTargetCriterion::MODE_EXCLUDE);

        $series->delete();

        $this->assertSame(0, $target->criteria()->count());
    }

    public function test_a_criterion_left_from_before_is_labelled_as_deleted(): void
    {
        $criterion = $this->criterion($this->target(), DiscordTargetCriterion::TYPE_TAG, 999999);

        $this->assertSame('deleted #999999', $criterion->label());
    }
}
