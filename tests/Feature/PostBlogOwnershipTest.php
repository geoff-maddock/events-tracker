<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\ContentType;
use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Posts and blogs: edits are authorized before anything is saved, and
 * ownership (created_by) comes from the signed-in user, never the request.
 */
class PostBlogOwnershipTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    private function makeUser(?string $group = null): User
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        if ($group) {
            $user->assignGroup($group);
        }

        return $user->fresh();
    }

    private function postBy(User $user): Post
    {
        return Post::factory()->create([
            'thread_id' => Thread::factory()->create()->id,
            'body' => 'original body',
            'created_by' => $user->id,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);
    }

    public function test_other_user_cannot_edit_a_post_on_the_web(): void
    {
        $post = $this->postBy($this->makeUser());
        $other = $this->makeUser();

        $this->actingAs($other)->get(route('posts.edit', $post))->assertForbidden();
        $this->actingAs($other)
            ->put(route('posts.update', $post), [
                'body' => 'defaced',
                'visibility_id' => Visibility::VISIBILITY_PUBLIC,
                'thread_id' => $post->thread_id,
            ])
            ->assertForbidden();

        $this->assertSame('original body', $post->fresh()->body);
    }

    public function test_owner_can_edit_a_post_on_the_web(): void
    {
        $owner = $this->makeUser();
        $post = $this->postBy($owner);

        $this->actingAs($owner)
            ->put(route('posts.update', $post), [
                'body' => 'edited body',
                'visibility_id' => Visibility::VISIBILITY_PUBLIC,
                'thread_id' => $post->thread_id,
            ])
            ->assertRedirect();

        $this->assertSame('edited body', $post->fresh()->body);
    }

    public function test_other_user_cannot_edit_a_blog_on_the_web(): void
    {
        $blog = Blog::factory()->create(['created_by' => $this->makeUser()->id, 'body' => 'original body']);
        $other = $this->makeUser();

        $this->actingAs($other)->get(route('blogs.edit', $blog))->assertForbidden();
        $this->actingAs($other)
            ->put(route('blogs.update', $blog), [
                'name' => 'Defaced',
                'slug' => $blog->slug,
                'body' => 'defaced',
                'visibility_id' => Visibility::VISIBILITY_PUBLIC,
                'content_type_id' => ContentType::PLAIN_TEXT,
            ])
            ->assertForbidden();

        $this->assertSame('original body', $blog->fresh()->body);
    }

    public function test_post_owner_cannot_reassign_ownership_via_api(): void
    {
        $owner = $this->makeUser();
        $victim = $this->makeUser();
        $post = $this->postBy($owner);

        $this->actingAs($owner, 'sanctum')
            ->patchJson('/api/posts/'.$post->id, ['body' => 'patched body', 'created_by' => $victim->id])
            ->assertOk();

        $post->refresh();
        $this->assertSame('patched body', $post->body);
        $this->assertSame($owner->id, (int) $post->created_by);
    }

    public function test_api_blog_store_is_owned_by_the_caller(): void
    {
        $author = $this->makeUser();
        $victim = $this->makeUser();
        $slug = 'zz-owned-'.uniqid();

        $this->actingAs($author, 'sanctum')
            ->postJson('/api/blogs', [
                'name' => 'ZZ Owned Blog',
                'slug' => $slug,
                'body' => 'Blog body text',
                'visibility_id' => Visibility::VISIBILITY_PUBLIC,
                'content_type_id' => ContentType::PLAIN_TEXT,
                'created_by' => $victim->id,
                'allow_html' => 1,
            ])
            ->assertOk();

        $blog = Blog::where('slug', $slug)->firstOrFail();
        $this->assertSame($author->id, (int) $blog->created_by);
        $this->assertNotSame(1, (int) $blog->allow_html);
    }
}
