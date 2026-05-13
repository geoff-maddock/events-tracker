<?php

namespace Tests\Feature\Web;

use App\Models\Entity;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntitiesControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_index_loads(): void
    {
        $this->get('/entities')->assertOk();
    }

    public function test_show_loads_for_existing_entity(): void
    {
        $entity = Entity::factory()->create();

        $this->get('/entities/'.$entity->slug)->assertOk();
    }

    public function test_show_returns_404_for_missing_entity(): void
    {
        $this->get('/entities/does-not-exist-zz-'.uniqid())->assertNotFound();
    }

    public function test_create_form_requires_auth(): void
    {
        $response = $this->get('/entities/create');

        $response->assertStatus(302);
    }

    public function test_create_form_loads_for_authenticated_user(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->actingAs($user)->get('/entities/create')->assertOk();
    }
}
