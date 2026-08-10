<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Thread;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A thread's "last activity" must track real activity — posts made or edited,
 * and edits to the thread itself — and must not move when someone merely
 * reads the thread.
 */
class ThreadActivityTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    /**
     * Backdate the row directly so the test isn't racing the same-second clock.
     */
    protected function backdate(Thread $thread): Carbon
    {
        $stale = Carbon::now()->subMonth()->startOfSecond();

        DB::table('threads')->where('id', $thread->id)->update([
            'created_at' => $stale,
            'updated_at' => $stale,
        ]);

        return $stale;
    }

    protected function updatedAt(Thread $thread): string
    {
        return (string) DB::table('threads')->where('id', $thread->id)->value('updated_at');
    }

    public function test_viewing_a_thread_counts_the_view_without_bumping_last_activity(): void
    {
        $thread = Thread::factory()->create(['views' => 0]);
        $stale = $this->backdate($thread);

        $this->signIn();
        $this->get('/threads/'.$thread->id)->assertOk();

        $this->assertSame(
            $stale->toDateTimeString(),
            Carbon::parse($this->updatedAt($thread))->toDateTimeString(),
            'reading a thread stamped it as updated'
        );
        $this->assertSame(1, (int) DB::table('threads')->where('id', $thread->id)->value('views'));
    }

    public function test_making_a_post_bumps_last_activity(): void
    {
        $thread = Thread::factory()->create();
        $stale = $this->backdate($thread);

        Post::factory()->create(['thread_id' => $thread->id]);

        $this->assertTrue(
            Carbon::parse($this->updatedAt($thread))->greaterThan($stale),
            'posting to a thread did not register as activity'
        );
    }

    public function test_editing_a_post_bumps_last_activity(): void
    {
        $thread = Thread::factory()->create();
        $post = Post::factory()->create(['thread_id' => $thread->id]);
        $stale = $this->backdate($thread);

        $post->body = 'edited body';
        $post->save();

        $this->assertTrue(
            Carbon::parse($this->updatedAt($thread))->greaterThan($stale),
            'editing a post did not register as activity'
        );
    }

    public function test_last_activity_accessor_prefers_the_newer_of_thread_and_post(): void
    {
        $thread = Thread::factory()->create();
        $post = Post::factory()->create(['thread_id' => $thread->id]);

        // thread edited more recently than its last post
        DB::table('posts')->where('id', $post->id)->update(['updated_at' => Carbon::now()->subWeek()]);
        DB::table('threads')->where('id', $thread->id)->update(['updated_at' => Carbon::now()->subDay()]);

        $fresh = Thread::find($thread->id);
        $this->assertSame(
            $fresh->updated_at->toDateTimeString(),
            $fresh->last_activity_at->toDateTimeString()
        );

        // and the other way round: a post newer than the thread's own edit
        DB::table('posts')->where('id', $post->id)->update(['updated_at' => Carbon::now()]);

        $fresh = Thread::with('lastPost')->find($thread->id);
        $this->assertSame(
            $fresh->lastPost->updated_at->toDateTimeString(),
            $fresh->last_activity_at->toDateTimeString()
        );
    }

    public function test_last_activity_accessor_works_without_the_eager_loaded_relation(): void
    {
        $thread = Thread::factory()->create();
        $this->backdate($thread);
        Post::factory()->create(['thread_id' => $thread->id]);

        $fresh = Thread::find($thread->id);
        $this->assertFalse($fresh->relationLoaded('lastPost'));
        $this->assertSame(
            Carbon::parse($this->updatedAt($thread))->toDateTimeString(),
            $fresh->last_activity_at->toDateTimeString()
        );
    }
}
