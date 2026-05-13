<?php

namespace Tests\Feature\Web;

use App\Models\Blog;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogsControllerTest extends TestCase
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
        $this->get('/blogs')->assertOk();
    }

    public function test_show_loads_for_existing_blog(): void
    {
        $blog = Blog::factory()->create();

        $this->get('/blogs/'.$blog->slug)->assertOk();
    }

    public function test_create_form_requires_auth(): void
    {
        $this->get('/blogs/create')->assertStatus(302);
    }

    public function test_create_form_loads_for_authenticated_user(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->actingAs($user)->get('/blogs/create')->assertOk();
    }
}
