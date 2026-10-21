<?php

namespace Tests\Feature;

use App\Mail\FollowingPostUpdate;
use App\Mail\FollowingThreadUpdate;
use App\Models\Blog;
use App\Models\Event;
use App\Models\Follow;
use App\Models\Menu;
use App\Models\Post;
use App\Models\Profile;
use App\Models\Series;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use App\Services\FollowerNotifier;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Threads, posts and blogs follow the same visibility rule on every read path:
 * the API, site search and follower mail, not only the web lists.
 */
class ForumContentVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $author;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();

        $this->author = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
    }

    private function member(bool $admin = false): User
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        if ($admin) {
            $user->assignGroup('admin');
        }

        return $user;
    }

    /** @return array<string, string> */
    private function token(User $user): array
    {
        return ['Accept' => 'application/json', 'Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken];
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function thread(int $visibility, array $attributes = []): Thread
    {
        // models stamp created_by from the signed-in user
        $this->actingAs($this->author);
        $thread = Thread::factory()->create(['visibility_id' => $visibility] + $attributes);
        $this->app['auth']->forgetGuards();

        return $thread;
    }

    private function reply(Thread $thread, int $visibility = Visibility::VISIBILITY_PUBLIC): Post
    {
        $this->actingAs($this->author);
        $post = Post::factory()->create(['thread_id' => $thread->id, 'visibility_id' => $visibility]);
        $this->app['auth']->forgetGuards();

        return $post;
    }

    public function test_the_api_thread_list_leaves_out_another_users_private_thread(): void
    {
        $hidden = $this->thread(Visibility::VISIBILITY_PRIVATE);
        $shown = $this->thread(Visibility::VISIBILITY_PUBLIC);

        $ids = collect($this->getJson('/api/threads?limit=100', $this->token($this->member(true)))
            ->assertOk()->json('data'))->pluck('id');

        $this->assertContains($shown->id, $ids);
        $this->assertNotContains($hidden->id, $ids);
    }

    public function test_the_api_thread_posts_list_is_not_found_for_a_private_thread(): void
    {
        $thread = $this->thread(Visibility::VISIBILITY_PRIVATE);
        $this->reply($thread);

        $this->getJson('/api/threads/'.$thread->id.'/posts', $this->token($this->member(true)))->assertNotFound();
    }

    public function test_the_author_can_list_posts_in_their_private_thread(): void
    {
        $thread = $this->thread(Visibility::VISIBILITY_PRIVATE);
        $this->reply($thread);

        $this->author->assignGroup('admin');
        $this->getJson('/api/threads/'.$thread->id.'/posts', $this->token($this->author))->assertOk();
    }

    public function test_the_api_post_page_is_not_found_in_a_private_thread(): void
    {
        $post = $this->reply($this->thread(Visibility::VISIBILITY_PRIVATE));

        $this->getJson('/api/posts/'.$post->id, $this->token($this->member(true)))->assertNotFound();
    }

    public function test_the_post_scope_stays_within_the_thread_it_is_chained_to(): void
    {
        $thread = $this->thread(Visibility::VISIBILITY_PUBLIC);
        $mine = $this->reply($thread);
        // an ownerless post elsewhere matched the ungrouped "or created_by is null"
        $elsewhere = $this->reply($this->thread(Visibility::VISIBILITY_PUBLIC), Visibility::VISIBILITY_PROPOSAL);
        Post::whereKey($elsewhere->id)->update(['created_by' => null]);

        $ids = Post::where('thread_id', $thread->id)->visible(null)->pluck('id');

        $this->assertEquals([$mine->id], $ids->all());
    }

    public function test_the_post_scope_leaves_out_posts_in_a_private_thread(): void
    {
        $post = $this->reply($this->thread(Visibility::VISIBILITY_PRIVATE));

        $this->assertFalse(Post::visible($this->member())->whereKey($post->id)->exists());
        $this->assertTrue(Post::visible($this->author)->whereKey($post->id)->exists());
    }

    public function test_a_private_blog_is_not_found_for_others_on_the_web_and_api(): void
    {
        $blog = Blog::factory()->create(['visibility_id' => Visibility::VISIBILITY_PRIVATE, 'created_by' => $this->author->id]);

        $this->get('/blogs/'.$blog->slug)->assertNotFound();
        $this->getJson('/api/blogs/'.$blog->slug, $this->token($this->member()))->assertNotFound();
    }

    public function test_the_author_can_read_their_private_blog_on_the_api(): void
    {
        $blog = Blog::factory()->create(['visibility_id' => Visibility::VISIBILITY_PRIVATE, 'created_by' => $this->author->id]);

        $this->getJson('/api/blogs/'.$blog->slug, $this->token($this->author))->assertOk();
    }

    public function test_the_event_page_leaves_out_a_private_thread_attached_to_it(): void
    {
        $event = Event::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        $this->thread(Visibility::VISIBILITY_PUBLIC, ['event_id' => $event->id, 'name' => 'Open thread Zq']);
        $this->thread(Visibility::VISIBILITY_PRIVATE, ['event_id' => $event->id, 'name' => 'Hidden thread Zq']);

        $this->get(route('events.show', $event->slug))
            ->assertOk()
            ->assertSee('Open thread Zq')
            ->assertDontSee('Hidden thread Zq');
    }

    public function test_the_series_page_leaves_out_a_private_thread_attached_to_it(): void
    {
        $series = Series::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        $hidden = $this->thread(Visibility::VISIBILITY_PRIVATE, ['name' => 'Hidden thread Zq']);
        $series->threads()->attach($hidden->id);

        $this->get(route('series.show', $series->slug))
            ->assertOk()
            ->assertDontSee('Hidden thread Zq');
    }

    public function test_a_public_profile_leaves_out_followed_threads_the_viewer_cannot_see(): void
    {
        $hidden = $this->thread(Visibility::VISIBILITY_PRIVATE, ['name' => 'Hidden thread Zq']);
        Profile::where('user_id', $this->author->id)->delete();
        Profile::factory()->create(['user_id' => $this->author->id, 'setting_public_profile' => 1]);
        Follow::create(['object_type' => 'thread', 'object_id' => $hidden->id, 'user_id' => $this->author->id]);

        $this->get('/users/'.$this->author->id.'?tabs[events]=created&tabs[following]=threads')
            ->assertOk()
            ->assertDontSee('Hidden thread Zq');
    }

    public function test_the_about_page_leaves_out_a_private_blog(): void
    {
        $menu = Menu::find(1) ?? Menu::factory()->create(['id' => 1]);
        Blog::factory()->create(['menu_id' => $menu->id, 'visibility_id' => Visibility::VISIBILITY_PUBLIC, 'body' => 'Open blog Zq']);
        Blog::factory()->create(['menu_id' => $menu->id, 'visibility_id' => Visibility::VISIBILITY_PRIVATE, 'body' => 'Hidden blog Zq', 'created_by' => $this->author->id]);

        $this->get('/about')
            ->assertOk()
            ->assertSee('Open blog Zq')
            ->assertDontSee('Hidden blog Zq');
    }

    public function test_site_search_leaves_out_another_users_private_thread(): void
    {
        // a two-letter term takes the LIKE path: fulltext can't see rows in the test transaction
        $this->thread(Visibility::VISIBILITY_PRIVATE, ['name' => 'Qz hidden thread']);
        $shown = $this->thread(Visibility::VISIBILITY_PUBLIC, ['name' => 'Qz open thread']);

        $threads = app(SearchService::class)->all('Qz', $this->member())['threads'];

        $this->assertEquals([$shown->id], collect($threads->items())->pluck('id')->all());
    }

    public function test_tag_followers_are_not_mailed_about_a_thread_or_reply_they_cannot_see(): void
    {
        Mail::fake();
        $tag = Tag::factory()->create();
        $follower = $this->member();
        Profile::factory()->create([
            'user_id' => $follower->id,
            'setting_forum_update' => 1,
            'setting_notify_threads_by_follow' => 1,
        ]);
        Follow::create(['user_id' => $follower->id, 'object_type' => 'tag', 'object_id' => $tag->id]);

        $thread = $this->thread(Visibility::VISIBILITY_PRIVATE);
        $thread->tags()->attach($tag->id);
        $post = $this->reply($thread);

        app(FollowerNotifier::class)->thread($thread);
        app(FollowerNotifier::class)->post($post);

        Mail::assertNotSent(FollowingThreadUpdate::class);
        Mail::assertNotSent(FollowingPostUpdate::class);

        // the same follower does hear about a public one
        $thread->update(['visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        app(FollowerNotifier::class)->thread($thread->fresh());

        Mail::assertSent(FollowingThreadUpdate::class, 1);
    }
}
