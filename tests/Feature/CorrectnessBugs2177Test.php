<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Event;
use App\Models\EventResponse;
use App\Models\Link;
use App\Models\Post;
use App\Models\ResponseType;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Small correctness bugs found in the Sept 2026 review (#2177).
 */
class CorrectnessBugs2177Test extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    private function entityWithLinks(): Entity
    {
        $entity = Entity::factory()->create();
        foreach ([
            ['https://example.com/site', now()->subDays(3)],
            ['https://Artist.Bandcamp.com/album/later', now()->subDay()],
            ['https://artist.bandcamp.com/album/first', now()->subDays(2)],
            ['https://soundcloud.com/artist', now()->subDays(2)],
        ] as [$url, $createdAt]) {
            $link = Link::factory()->create(['url' => $url]);
            $link->forceFill(['created_at' => $createdAt])->save();
            $entity->links()->attach($link->id);
        }

        return $entity;
    }

    public function test_bandcamp_and_soundcloud_links_match_with_links_loaded_or_not(): void
    {
        $entity = $this->entityWithLinks();

        foreach ([Entity::find($entity->id), Entity::with('links')->find($entity->id)] as $model) {
            $this->assertSame('https://artist.bandcamp.com/album/first', $model->bandcampLink?->url);
            $this->assertSame('https://soundcloud.com/artist', $model->soundcloudLink?->url);
        }
    }

    public function test_link_accessors_return_null_without_a_matching_link(): void
    {
        $entity = Entity::factory()->create();
        $entity->links()->attach(Link::factory()->create(['url' => 'https://example.com'])->id);

        $loaded = Entity::with('links')->find($entity->id);
        $this->assertNull($loaded->bandcampLink);
        $this->assertNull($loaded->soundcloudLink);
    }

    public function test_attending_count_counts_only_attending_responses(): void
    {
        $event = Event::factory()->create();
        foreach ([ResponseType::ATTENDING, ResponseType::ATTENDING, ResponseType::INTERESTED, ResponseType::IGNORE] as $type) {
            EventResponse::create([
                'event_id' => $event->id,
                'user_id' => User::factory()->create()->id,
                'response_type_id' => $type,
            ]);
        }

        $this->assertSame(2, $event->fresh()->attending_count);
    }

    public function test_a_plain_string_flash_message_is_shown_rather_than_an_empty_alert(): void
    {
        $this->withSession(['flash_message' => 'Plain string notice'])
            ->get('/')
            ->assertOk()
            ->assertSee('"Plain string notice"', false);
    }

    public function test_deleting_a_comment_flashes_a_titled_message(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $entity = Entity::factory()->create();
        // Comment stamps created_by from the signed-in user
        $this->actingAs($user);
        $comment = $entity->comments()->create(['message' => 'A comment to delete']);

        $this->delete("/entities/{$entity->slug}/comments/{$comment->id}")
            ->assertSessionHas('flash_message.title', 'Success')
            ->assertSessionHas('flash_message.message', 'Your comment has been deleted.');
    }

    public function test_a_reply_tags_the_post_it_created(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $thread = Thread::factory()->create();
        $tag = Tag::factory()->create(['name' => 'Zzreply', 'slug' => 'zzreply']);

        $this->actingAs($user)
            ->post("/threads/{$thread->id}/posts", ['body' => 'A reply with a tag', 'tag_list' => [$tag->id]])
            ->assertRedirect();

        $post = Post::where('body', 'A reply with a tag')->sole();
        $this->assertSame($user->id, (int) $post->created_by);
        $this->assertSame([$tag->id], $post->tags()->pluck('tags.id')->all());
    }

    public function test_an_empty_reply_is_rejected(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $thread = Thread::factory()->create();
        $before = Post::count();

        $this->actingAs($user)
            ->post("/threads/{$thread->id}/posts", ['body' => ''])
            ->assertSessionHasErrors('body');

        $this->assertSame($before, Post::count());
    }
}
