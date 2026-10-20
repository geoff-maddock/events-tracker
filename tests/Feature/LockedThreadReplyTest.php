<?php

namespace Tests\Feature;

use App\Models\Thread;
use App\Models\User;
use App\Models\UserStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Locking a thread only hid the reply form; a direct POST still added a reply
 * and notified the thread's followers (#2343).
 */
class LockedThreadReplyTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();

        $this->member = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
    }

    private function thread(bool $locked): Thread
    {
        $thread = Thread::factory()->create();
        if ($locked) {
            $thread->forceFill(['locked_by' => $this->member->id, 'locked_at' => Carbon::now()])->save();
        }

        return $thread->fresh();
    }

    public function test_a_web_reply_to_a_locked_thread_is_refused(): void
    {
        $thread = $this->thread(true);

        $this->actingAs($this->member)
            ->post('/threads/'.$thread->id.'/posts', ['body' => 'A reply to a locked thread'])
            ->assertForbidden();

        $this->assertSame(0, $thread->posts()->count());
    }

    public function test_an_api_reply_to_a_locked_thread_is_refused(): void
    {
        $thread = $this->thread(true);

        $this->actingAs($this->member, 'sanctum')
            ->postJson('/api/posts', ['thread_id' => $thread->id, 'body' => 'A reply to a locked thread'])
            ->assertForbidden();

        $this->assertSame(0, $thread->posts()->count());
    }

    public function test_a_web_reply_to_an_open_thread_is_saved(): void
    {
        $thread = $this->thread(false);

        $this->actingAs($this->member)
            ->post('/threads/'.$thread->id.'/posts', ['body' => 'A reply to an open thread'])
            ->assertRedirect();

        $this->assertSame(1, $thread->posts()->count());
    }
}
