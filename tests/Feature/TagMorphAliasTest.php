<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Event;
use App\Models\Follow;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Morph alias regression (issue #1990): the morphMap aliased Tag as 'tags'
 * and Event as 'events' while every write path stores the singular strings,
 * so Tag::follows() (and the tags/popular follow counts built on it) always
 * matched zero rows, and morphTo resolution for event rows failed.
 */
class TagMorphAliasTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->actingAs($this->user, 'sanctum');
    }

    /** @test */
    public function following_a_tag_is_visible_through_the_morph_relation(): void
    {
        $tag = Tag::factory()->create();

        $this->postJson('/api/tags/'.$tag->slug.'/follow')->assertStatus(200);

        // Written as object_type='tag'; the corrected alias makes the
        // morphMany see it (pre-fix this counted 0).
        $this->assertSame(1, $tag->follows()->count());

        // And morphTo resolves back to the Tag.
        $follow = Follow::where('object_type', 'tag')->where('object_id', $tag->id)->first();
        $this->assertInstanceOf(Tag::class, $follow->object);
        $this->assertSame($tag->id, $follow->object->id);
    }

    /** @test */
    public function tags_popular_counts_follows_in_the_popularity_score(): void
    {
        $tag = Tag::factory()->create(['name' => 'Morphstyle ZZ', 'slug' => 'morphstyle-zz']);

        $this->postJson('/api/tags/'.$tag->slug.'/follow')->assertStatus(200);

        $response = $this->getJson('/api/tags/popular?filters[name]=Morphstyle ZZ')
            ->assertStatus(200);

        $row = collect($response->json('data'))->firstWhere('slug', 'morphstyle-zz');
        $this->assertNotNull($row, 'Expected the tag in the popular listing.');

        // No events, one follow: score 1. Pre-fix withCount('follows')
        // matched nothing and the score was 0.
        $this->assertSame(1, $row['popularity_score']);
    }

    /** @test */
    public function unfollowing_removes_the_morph_relation_row(): void
    {
        $tag = Tag::factory()->create();

        $this->postJson('/api/tags/'.$tag->slug.'/follow')->assertStatus(200);
        $this->postJson('/api/tags/'.$tag->slug.'/unfollow')->assertStatus(204);

        $this->assertSame(0, $tag->follows()->count());
    }

    /** @test */
    public function event_comments_resolve_their_commentable_event(): void
    {
        $event = Event::factory()->create();

        // CommentsController writes commentable_type='event'; under the old
        // 'events' alias this morphTo could not resolve.
        $comment = Comment::create([
            'message' => 'morph resolution check',
            'commentable_type' => 'event',
            'commentable_id' => $event->id,
        ]);

        $resolved = $comment->fresh()->commentable;
        $this->assertInstanceOf(Event::class, $resolved);
        $this->assertSame($event->id, $resolved->id);
    }
}
