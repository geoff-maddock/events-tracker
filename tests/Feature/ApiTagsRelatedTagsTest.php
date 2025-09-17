<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTagsRelatedTagsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /** @test */
    public function it_returns_related_tags_for_a_tag(): void
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user, 'sanctum');

        // Create test tags
        $mainTag = Tag::factory()->create(['name' => 'Main Tag', 'slug' => 'main-tag']);
        $relatedTag1 = Tag::factory()->create(['name' => 'Related Tag 1', 'slug' => 'related-tag-1']);
        $relatedTag2 = Tag::factory()->create(['name' => 'Related Tag 2', 'slug' => 'related-tag-2']);

        // Create events that link the tags together
        $event1 = Event::factory()->create(['created_by' => $user->id]);
        $event2 = Event::factory()->create(['created_by' => $user->id]);

        // Attach tags to events to create relationships
        $event1->tags()->attach([$mainTag->id, $relatedTag1->id]);
        $event2->tags()->attach([$mainTag->id, $relatedTag1->id, $relatedTag2->id]);

        $response = $this->getJson("/api/tags/{$mainTag->slug}/related-tags");

        $response->assertStatus(200);
        $data = $response->json();

        // Check that related tags are returned
        $this->assertIsArray($data);
        $this->assertArrayHasKey('Related Tag 1', $data);
        $this->assertEquals(2, $data['Related Tag 1']); // Should appear in 2 events
        $this->assertArrayHasKey('Related Tag 2', $data);
        $this->assertEquals(1, $data['Related Tag 2']); // Should appear in 1 event
    }

    /** @test */
    public function it_returns_empty_array_for_tag_with_no_related_tags(): void
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user, 'sanctum');

        $tag = Tag::factory()->create(['name' => 'Isolated Tag', 'slug' => 'isolated-tag']);

        $response = $this->getJson("/api/tags/{$tag->slug}/related-tags");

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertIsArray($data);
        $this->assertEmpty($data);
    }

    /** @test */
    public function it_returns_404_for_non_existent_tag(): void
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/tags/non-existent-tag/related-tags');

        $response->assertStatus(404);
    }
}