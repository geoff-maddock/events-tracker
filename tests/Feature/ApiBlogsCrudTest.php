<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\ContentType;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiBlogsCrudTest extends TestCase
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
        return array_merge([
            'name' => 'Test Blog ZZ',
            'slug' => 'test-blog-zz-'.uniqid(),
            'body' => 'A blog post body with enough length',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'content_type_id' => ContentType::PLAIN_TEXT,
        ], $overrides);
    }

    public function test_store_creates_a_blog(): void
    {
        $slug = 'test-blog-'.uniqid();
        $this->postJson('/api/blogs', $this->payload(['slug' => $slug]))
            ->assertStatus(200);

        $this->assertDatabaseHas('blogs', ['slug' => $slug]);
    }

    public function test_store_rejects_payload_missing_required_fields(): void
    {
        $this->postJson('/api/blogs', ['name' => 'incomplete'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['slug', 'body', 'visibility_id', 'content_type_id']);
    }

    public function test_store_rejects_duplicate_slug(): void
    {
        $existing = Blog::factory()->create();

        $this->postJson('/api/blogs', $this->payload(['slug' => $existing->slug]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_update_replaces_blog_for_owner(): void
    {
        $blog = Blog::factory()->create(['created_by' => $this->user->id]);

        $this->putJson('/api/blogs/'.$blog->slug, $this->payload([
            'slug' => $blog->slug,
            'name' => 'Replaced Name ZZ',
        ]))->assertStatus(200);

        $this->assertSame('Replaced Name ZZ', $blog->fresh()->name);
    }

    public function test_patch_partial_update_for_owner(): void
    {
        $blog = Blog::factory()->create([
            'created_by' => $this->user->id,
            'name' => 'Original ZZ',
        ]);

        $this->patchJson('/api/blogs/'.$blog->slug, ['name' => 'Patched ZZ'])
            ->assertStatus(200);

        $this->assertSame('Patched ZZ', $blog->fresh()->name);
    }

    public function test_destroy_deletes_blog_for_owner(): void
    {
        $blog = Blog::factory()->create(['created_by' => $this->user->id]);

        // destroy returns a RedirectResponse (302) to blogs.index.
        $this->deleteJson('/api/blogs/'.$blog->slug)->assertStatus(302);

        $this->assertNull(Blog::find($blog->id));
    }

    public function test_show_returns_existing_blog(): void
    {
        $blog = Blog::factory()->create();

        $this->getJson('/api/blogs/'.$blog->slug)
            ->assertStatus(200)
            ->assertJson(['id' => $blog->id, 'slug' => $blog->slug]);
    }
}
