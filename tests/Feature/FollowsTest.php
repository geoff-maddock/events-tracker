<?php

namespace Tests\Feature;

use App\Entity;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class FollowsTest extends TestCase
{
    // use DatabaseMigrations;

    /** @test */
    public function a_guest_cannot_follow_anything()
    {
        // This test assumes an entity with an id = 1 exists
        $this->withExceptionHandling()
            ->post('/entities/1/follow')
            ->assertRedirect('/');
    }

    /** @test */
    public function an_authenticated_user_can_follow_any_entity()
    {
        $this->signIn();

        $entity = create('App\Entity');

        $this->post('/entities/' . $entity->id . '/follow');

        $this->assertCount(1, $entity->follows);
    }
}
