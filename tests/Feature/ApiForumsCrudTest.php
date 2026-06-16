<?php

namespace Tests\Feature;

use App\Models\Forum;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiForumsCrudTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        // index()/filter() are gated by show_forum; the admin group bypasses all gates.
        $this->user->assignGroup('admin');
        $this->actingAs($this->user, 'sanctum');
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Forum ZZ',
            'slug' => 'test-forum-zz-'.uniqid(),
            'description' => 'A forum for testing',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ], $overrides);
    }

    public function test_index_returns_json_collection(): void
    {
        $forum = Forum::factory()->create();

        $this->getJson('/api/forums')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'current_page', 'total'])
            ->assertJsonFragment(['id' => $forum->id]);
    }

    public function test_filter_returns_json_collection(): void
    {
        Forum::factory()->create();

        $this->getJson('/api/forums/filter')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'current_page', 'total']);
    }

    public function test_store_creates_a_forum(): void
    {
        $slug = 'test-forum-'.uniqid();
        $this->postJson('/api/forums', $this->payload(['slug' => $slug]))
            ->assertStatus(200);

        $this->assertDatabaseHas('forums', ['slug' => $slug]);
    }

    public function test_store_ignores_non_fillable_short_field(): void
    {
        // 'short' is not a forums column; it must be ignored, not reach the INSERT
        // and 500 with "Unknown column 'short'" (EVENTREPO-W0).
        $slug = 'test-forum-short-'.uniqid();

        $this->postJson('/api/forums', $this->payload([
            'slug' => $slug,
            'short' => 'should be ignored',
        ]))->assertStatus(200);

        $this->assertDatabaseHas('forums', ['slug' => $slug]);
    }

    public function test_store_rejects_payload_missing_required_fields(): void
    {
        $this->postJson('/api/forums', ['description' => 'incomplete'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'slug', 'visibility_id']);
    }

    public function test_show_returns_existing_forum(): void
    {
        $forum = Forum::factory()->create();

        $this->getJson('/api/forums/'.$forum->id)
            ->assertStatus(200)
            ->assertJson(['id' => $forum->id]);
    }

    public function test_update_replaces_forum_for_owner(): void
    {
        $forum = Forum::factory()->create(['created_by' => $this->user->id]);

        $this->putJson('/api/forums/'.$forum->id, $this->payload([
            'name' => 'Replaced Forum ZZ',
        ]))->assertStatus(200);

        $this->assertSame('Replaced Forum ZZ', $forum->fresh()->name);
    }

    public function test_patch_partial_update_for_owner(): void
    {
        $forum = Forum::factory()->create([
            'created_by' => $this->user->id,
            'name' => 'Original Forum ZZ',
        ]);

        $this->patchJson('/api/forums/'.$forum->id, ['name' => 'Patched Forum ZZ'])
            ->assertStatus(200);

        $this->assertSame('Patched Forum ZZ', $forum->fresh()->name);
    }

    public function test_destroy_deletes_forum(): void
    {
        $forum = Forum::factory()->create(['created_by' => $this->user->id]);

        // Normalized to a JSON API: destroy returns 204 No Content.
        $this->deleteJson('/api/forums/'.$forum->id)->assertNoContent();

        $this->assertNull(Forum::find($forum->id));
    }

    public function test_create_and_edit_html_form_routes_are_removed(): void
    {
        $forum = Forum::factory()->create();

        // The API is JSON-only (apiResource) — no HTML create/edit form endpoints.
        $this->getJson('/api/forums/create')->assertStatus(404);
        $this->getJson('/api/forums/'.$forum->id.'/edit')->assertStatus(404);
    }
}
