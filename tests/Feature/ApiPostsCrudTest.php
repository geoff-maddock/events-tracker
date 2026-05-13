<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiPostsCrudTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->actingAs($this->user, 'sanctum');
    }

    public function test_show_is_gated_behind_show_forum_ability(): void
    {
        // PostsController::show calls Gate::denies('show_forum'). No such
        // gate is registered, so all visitors are redirected. Assert the
        // current behavior so we notice if the gate is added later.
        $post = Post::factory()->create();

        $response = $this->getJson('/api/posts/'.$post->id);

        $this->assertContains($response->status(), [302, 403]);
    }

    public function test_update_replaces_post_for_owner(): void
    {
        $post = Post::factory()->create(['created_by' => $this->user->id]);

        $this->putJson('/api/posts/'.$post->id, [
            'body' => 'Replaced body content ZZ',
            'visibility_id' => 1,
            'thread_id' => $post->thread_id,
        ])->assertStatus(200);

        $this->assertSame('Replaced body content ZZ', $post->fresh()->body);
    }

    public function test_update_rejects_non_owner(): void
    {
        $owner = User::factory()->create();
        $post = Post::factory()->create(['created_by' => $owner->id]);

        // Acting as a different user (this->user).
        $response = $this->putJson('/api/posts/'.$post->id, [
            'body' => 'Hijacked content ZZ',
            'visibility_id' => 1,
            'thread_id' => $post->thread_id,
        ]);

        // unauthorized() throws AuthorizationException → 403 under the
        // standard handler, but the project's exception path returns one of
        // these depending on configuration.
        $this->assertContains($response->status(), [302, 401, 403]);
        $this->assertNotSame('Hijacked content ZZ', $post->fresh()->body);
    }

    public function test_patch_partial_update_for_owner(): void
    {
        $post = Post::factory()->create([
            'created_by' => $this->user->id,
            'body' => 'Original body ZZ',
        ]);

        $this->patchJson('/api/posts/'.$post->id, ['body' => 'Patched ZZ'])
            ->assertStatus(200);

        $this->assertSame('Patched ZZ', $post->fresh()->body);
    }

    public function test_destroy_deletes_post_for_owner(): void
    {
        $post = Post::factory()->create(['created_by' => $this->user->id]);

        $this->deleteJson('/api/posts/'.$post->id);

        $this->assertNull(Post::find($post->id));
    }

    public function test_update_rejects_missing_required_fields(): void
    {
        $post = Post::factory()->create(['created_by' => $this->user->id]);

        $this->putJson('/api/posts/'.$post->id, [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['body', 'visibility_id', 'thread_id']);
    }
}
