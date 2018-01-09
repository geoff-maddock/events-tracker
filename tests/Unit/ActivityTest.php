<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ActivityTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function it_records_activity_when_a_thread_is_created()
    {
        $this->signIn();

        $thread = create('App\Thread');

        $this->assetDatabaseHas('activities', [
            'object_table' => get_class($thread),
            'user_id' => auth()->id(),
            'object_id' => $thread->id,
            'action_id' => 1,
            ]);
    }
}