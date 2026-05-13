<?php

namespace Tests\Unit\Models;

use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThreadModelTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_owned_by_returns_true_for_creator(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $thread = Thread::factory()->create();

        $this->assertTrue($thread->ownedBy($user));
    }

    public function test_owned_by_returns_false_for_non_creator(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($owner);
        $thread = Thread::factory()->create();

        $this->assertFalse($thread->ownedBy($other));
    }

    public function test_is_recent_returns_true_for_freshly_created_thread(): void
    {
        // ThreadFactory uses a randomized created_at within the past year; pin it.
        $thread = Thread::factory()->create();
        $thread->forceFill(['created_at' => Carbon::now()])->save();

        $this->assertTrue($thread->fresh()->isRecent());
    }

    public function test_is_recent_returns_false_for_older_thread(): void
    {
        $thread = Thread::factory()->create();
        $thread->forceFill(['created_at' => Carbon::now()->subDays(2)])->save();

        $this->assertFalse($thread->fresh()->isRecent());
    }

    public function test_add_post_creates_post_attached_to_thread(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $thread = Thread::factory()->create();

        $post = $thread->addPost([
            'name' => 'ZZ-Post-Name',
            'body' => 'A body for the post.',
        ]);

        $this->assertInstanceOf(Post::class, $post);
        $this->assertSame($thread->id, $post->thread_id);
    }

    public function test_last_post_at_attribute_returns_latest_post_created_at(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $thread = Thread::factory()->create();

        $thread->addPost(['name' => 'old', 'body' => 'old'])
            ->forceFill(['created_at' => Carbon::now()->subDays(2)])->save();
        $newest = $thread->addPost(['name' => 'new', 'body' => 'new']);

        $this->assertTrue($thread->fresh()->last_post_at->equalTo($newest->created_at));
    }

    public function test_path_returns_thread_url_path(): void
    {
        $thread = Thread::factory()->create();

        $this->assertStringContainsString('/threads/'.$thread->id, $thread->path());
    }
}
