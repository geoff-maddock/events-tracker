<?php

namespace Tests\Feature\Web;

use App\Models\Blog;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogsControllerSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['user_status_id' => UserStatus::ACTIVE]));
    }

    public function test_blogs_index_renders(): void
    {
        Blog::factory()->count(2)->create();

        $this->get('/blogs')->assertOk();
    }

    public function test_blogs_show_renders(): void
    {
        // the factory picks a random visibility; the show page needs a public one
        $blog = Blog::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC]);

        $this->get('/blogs/'.$blog->slug)->assertOk();
    }

    public function test_blogs_create_renders(): void
    {
        $this->get('/blogs/create')->assertOk();
    }

    public function test_blogs_reset_redirects(): void
    {
        $this->get('/blogs/reset')->assertRedirect('/blogs');
    }
}
