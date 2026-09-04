<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventType;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for issue #2120: a tag_list entry that names an existing
 * tag must reuse it rather than create a duplicate.
 */
class TagListResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $user;

    private Tag $diy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->diy = Tag::factory()->create(['name' => 'Diy', 'slug' => 'diy', 'tag_type_id' => 1]);
    }

    public function test_resolve_list_reuses_an_existing_tag_by_name_regardless_of_case(): void
    {
        $before = Tag::count();

        $tags = Tag::resolveList(['diy', 'DIY', ' Diy '], $this->user);

        $this->assertCount(1, $tags);
        $this->assertSame($this->diy->id, $tags->first()->id);
        $this->assertFalse($tags->first()->wasRecentlyCreated);
        $this->assertSame($before, Tag::count());
    }

    public function test_resolve_list_accepts_ids_and_creates_a_missing_name_exactly_once(): void
    {
        $before = Tag::count();

        $tags = Tag::resolveList([(string) $this->diy->id, 'noise rock', 'Noise Rock', '', null], $this->user);

        $this->assertCount(2, $tags);
        $this->assertSame($before + 1, Tag::count());

        $created = $tags->firstWhere('slug', 'noise-rock');
        $this->assertNotNull($created);
        $this->assertTrue($created->wasRecentlyCreated);
        $this->assertSame('Noise Rock', $created->name);
        $this->assertSame(Tag::DEFAULT_TAG_TYPE_ID, $created->tag_type_id);
        $this->assertSame($this->user->id, $created->created_by);
        $this->assertDatabaseHas('activities', ['object_table' => 'Tag', 'object_id' => $created->id, 'user_id' => $this->user->id]);
    }

    public function test_api_event_store_with_an_existing_tag_name_attaches_it_without_duplicating(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $this->postJson('/api/events', $this->eventPayload(['tag_list' => ['diy', 'shoegaze']]))->assertOk();

        $event = Event::where('slug', 'zz-tag-test')->firstOrFail();
        $this->assertSame(1, Tag::where('slug', 'diy')->count());
        $this->assertSame(1, Tag::where('slug', 'shoegaze')->count());
        $this->assertEqualsCanonicalizing(
            [$this->diy->id, Tag::where('slug', 'shoegaze')->value('id')],
            $event->tags->pluck('id')->all()
        );
    }

    public function test_api_event_update_with_an_existing_tag_name_syncs_without_duplicating(): void
    {
        $this->actingAs($this->user, 'sanctum');
        $event = Event::factory()->create(['created_by' => $this->user->id]);

        $this->patchJson('/api/events/'.$event->slug, ['tag_list' => ['Diy']])->assertOk();

        $this->assertSame(1, Tag::where('slug', 'diy')->count());
        $this->assertSame([$this->diy->id], $event->fresh()->tags->pluck('id')->all());
    }

    public function test_web_event_store_with_an_existing_tag_name_attaches_it_without_duplicating(): void
    {
        $this->actingAs($this->user)->post('/events', $this->eventPayload(['tag_list' => ['diy']]))->assertRedirect();

        $event = Event::where('slug', 'zz-tag-test')->firstOrFail();
        $this->assertSame(1, Tag::where('slug', 'diy')->count());
        $this->assertSame([$this->diy->id], $event->tags->pluck('id')->all());
    }

    private function eventPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'ZZ Tag Test',
            'slug' => 'zz-tag-test',
            'short' => 'A short blurb.',
            'description' => 'A longer description.',
            'event_type_id' => EventType::first()->id,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'is_benefit' => 0,
            'do_not_repost' => 0,
            'event_status_id' => 1,
            'start_at' => '2026-10-15 20:00:00',
            'end_at' => '2026-10-15 23:00:00',
        ], $overrides);
    }
}
