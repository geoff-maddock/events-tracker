<?php

namespace Tests\Feature;

use App\Models\Thread;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ActivityTest extends TestCase
{
    //use DatabaseMigrations;

    /** @test */
    public function it_records_activity_when_a_thread_is_created()
    {
        $this->signIn();

        // use the factory class to create a new thread and post
        $thread = Thread::factory()->make();
        $this->post('/threads', $thread->toArray());

        // find the specific saved thread
        $savedThread = Thread::orderBy('created_at', 'desc')->first();

        // check that there was an activity created related to the thread
        $this->assertDatabaseHas('activities', [
            'object_table' => 'Thread',
            'user_id' => auth()->id(),
            'object_id' => $savedThread->id,
            'action_id' => 1,
        ]);
    }
}
