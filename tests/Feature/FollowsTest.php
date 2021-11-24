<?php

namespace Tests\Feature;

use App\Models\Entity;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FollowsTest extends TestCase
{
    // refresh database and run migrations before test
    use RefreshDatabase;

    // reseed the database
    protected $seed = true;

    /** @test */
    public function a_guest_cannot_follow_anything()
    {
        // This test assumes an entity with an id = 1 exists
        $this->withExceptionHandling()
            ->post('/entities/1/follow')
            ->assertRedirect('/login');
    }

    /** @test */
    public function an_authenticated_user_can_follow_any_entity()
    {
        $this->signIn();

        $entity = Entity::factory()->create();

        $this->post('/entities/' . $entity->id . '/follow');

        $this->assertCount(1, $entity->follows);
    }
}
