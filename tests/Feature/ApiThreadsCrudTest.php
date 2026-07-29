<?php

namespace Tests\Feature;

use App\Models\Forum;
use App\Models\Group;
use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiThreadsCrudTest extends TestCase
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

    private function payload(array $overrides = []): array
    {
        $forum = Forum::factory()->create();

        return array_merge([
            'name' => 'Thread Name ZZ',
            'body' => 'Initial thread body content',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'forum_id' => $forum->id,
        ], $overrides);
    }

    public function test_store_creates_a_thread(): void
    {
        $payload = $this->payload(['name' => 'ZZ Test Thread '.uniqid()]);

        // store returns a redirect to threads.index.
        $this->postJson('/api/threads', $payload)->assertStatus(302);

        $this->assertDatabaseHas('threads', ['name' => $payload['name']]);
    }

    public function test_store_rejects_missing_required_fields(): void
    {
        $this->postJson('/api/threads', ['name' => 'x'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'body', 'visibility_id', 'forum_id']);
    }

    public function test_update_replaces_thread_for_owner(): void
    {
        $this->actingAs($this->user);
        $thread = Thread::factory()->create();
        // Thread::boot's `creating` event sets created_by from Auth::user().
        // We acted as $this->user above so $thread is owned by them.

        $this->putJson('/api/threads/'.$thread->id, $this->payload([
            'name' => 'Replaced Thread Name',
        ]))->assertStatus(200);

        $this->assertSame('Replaced Thread Name', $thread->fresh()->name);
    }

    public function test_update_rejects_non_owner(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);
        $thread = Thread::factory()->create();

        // Now act as a different (still active) user.
        $this->actingAs($this->user, 'sanctum');

        $response = $this->putJson('/api/threads/'.$thread->id, $this->payload([
            'name' => 'Hijacked',
        ]));

        $this->assertContains($response->status(), [302, 401, 403]);
        $this->assertNotSame('Hijacked', $thread->fresh()->name);
    }

    public function test_patch_partial_update_for_owner(): void
    {
        $this->actingAs($this->user);
        $thread = Thread::factory()->create(['name' => 'Original ZZ']);

        $this->patchJson('/api/threads/'.$thread->id, ['name' => 'Patched ZZ'])
            ->assertStatus(200);

        $this->assertSame('Patched ZZ', $thread->fresh()->name);
    }

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $adminGroup = Group::where('name', 'admin')->first()
            ?? Group::factory()->create(['name' => 'admin']);
        $admin->groups()->attach($adminGroup->id);
        $this->actingAs($admin, 'sanctum');

        return $admin;
    }

    public function test_posts_lists_thread_posts_newest_first(): void
    {
        // Regression for issue #1988: the posts sub-list previously ordered
        // by threads.created_at (not in the query) and 500ed on any thread
        // that actually had posts.
        $this->actingAsAdmin();

        $thread = Thread::factory()->create();
        $older = Post::factory()->create([
            'thread_id' => $thread->id,
            'created_at' => now()->subDay(),
        ]);
        $newer = Post::factory()->create([
            'thread_id' => $thread->id,
            'created_at' => now(),
        ]);

        $response = $this->getJson('/api/threads/'.$thread->id.'/posts')
            ->assertStatus(200)
            ->assertJsonPath('total', 2);

        $ids = array_column($response->json('data'), 'id');
        $this->assertSame([$newer->id, $older->id], $ids);
    }

    public function test_posts_returns_empty_page_for_thread_without_posts(): void
    {
        $this->actingAsAdmin();

        $thread = Thread::factory()->create();

        $this->getJson('/api/threads/'.$thread->id.'/posts')
            ->assertStatus(200)
            ->assertJsonPath('total', 0)
            ->assertJsonPath('data', []);
    }

    public function test_posts_returns_404_for_missing_thread(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/api/threads/99999999/posts')
            ->assertStatus(404);
    }

    public function test_posts_denied_without_show_post_gate(): void
    {
        // setUp's plain user has no admin group, so show_post denies.
        $thread = Thread::factory()->create();
        Post::factory()->create(['thread_id' => $thread->id]);

        $this->getJson('/api/threads/'.$thread->id.'/posts')
            ->assertStatus(403);
    }
}
