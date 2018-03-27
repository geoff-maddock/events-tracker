<?php

namespace Tests\Unit;

use App\Thread;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ActivityTest extends TestCase
{
    //use DatabaseMigrations;

    /** @test */
    public function it_records_activity_when_a_thread_is_created()
    {
        $this->signIn();

        $thread = make('App\Thread');

        $this->post('/threads', $thread->toArray());

        $savedThread = Thread::orderBy('created_at', 'desc')->first();

        $this->assertDatabaseHas('activities', [
            'object_table' => 'Thread',
            'user_id' => auth()->id(),
            'object_id' => $savedThread->id,
            'action_id' => 1,
            ]);
    }
}