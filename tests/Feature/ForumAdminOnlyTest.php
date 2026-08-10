<?php

namespace Tests\Feature;

use App\Models\Forum;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Forums are site structure, so only admins may add, edit or remove one —
 * in the UI and, more importantly, at the route.
 */
class ForumAdminOnlyTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    protected function admin(): User
    {
        $admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $admin->assignGroup('admin');

        return $admin->fresh();
    }

    protected function member(): User
    {
        return User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
    }

    public function test_admin_sees_the_add_forum_button(): void
    {
        $this->actingAs($this->admin());

        $this->get('/forums')->assertOk()->assertSee('Add Forum', false);
    }

    /**
     * The listing is itself behind the show_forum gate, which today only the
     * admin group passes — so a non-admin never reaches the button. Pinned so
     * that widening show_forum can't quietly expose the create link.
     */
    public function test_non_admin_cannot_reach_the_forum_listing_at_all(): void
    {
        $this->actingAs($this->member());

        $this->get('/forums')->assertRedirect();
    }

    public function test_non_admin_cannot_reach_the_create_form_or_store_a_forum(): void
    {
        $this->actingAs($this->member());

        $this->get('/forums/create')->assertRedirect(route('forums.index'));

        $this->post('/forums', $this->payload('Sneaky Forum', 'sneaky-forum'))
            ->assertRedirect(route('forums.index'));

        $this->assertDatabaseMissing('forums', ['slug' => 'sneaky-forum']);
    }

    public function test_admin_can_store_a_forum(): void
    {
        $this->actingAs($this->admin());

        $this->post('/forums', $this->payload('Legit Forum', 'legit-forum'))
            ->assertRedirect(route('forums.index'));

        $this->assertDatabaseHas('forums', ['slug' => 'legit-forum']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(string $name, string $slug): array
    {
        return [
            'name' => $name,
            'slug' => $slug,
            'description' => 'A forum',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ];
    }

    /**
     * destroy carried no auth middleware at all, so an unauthenticated
     * request could delete a forum.
     */
    public function test_guests_cannot_destroy_a_forum(): void
    {
        $forum = Forum::factory()->create();

        $this->delete('/forums/'.$forum->id)->assertRedirect();

        $this->assertDatabaseHas('forums', ['id' => $forum->id]);
    }

    public function test_non_admin_cannot_destroy_a_forum(): void
    {
        $forum = Forum::factory()->create();
        $this->actingAs($this->member());

        $this->delete('/forums/'.$forum->id)->assertRedirect(route('forums.index'));

        $this->assertDatabaseHas('forums', ['id' => $forum->id]);
    }
}
