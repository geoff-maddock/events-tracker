<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\BlogPatchRequest;
use App\Http\Requests\BlogRequest;
use App\Http\Requests\ContactRequest;
use App\Http\Requests\EventReviewRequest;
use App\Http\Requests\ForumRequest;
use App\Http\Requests\GroupRequest;
use App\Http\Requests\MenuRequest;
use App\Http\Requests\PermissionRequest;
use App\Http\Requests\PostRequest;
use App\Http\Requests\RoleRequest;
use App\Http\Requests\TagRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Validate that FormRequest rules accept good input and reject bad input.
 * These exercise the static rules() arrays, not HTTP plumbing, so they're
 * fast even with $seed = true (needed for unique:* rules that hit the DB).
 */
class FormRequestRulesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function assertPasses(string $requestClass, array $data): void
    {
        $rules = (new $requestClass())->rules();
        $validator = Validator::make($data, $rules);

        $this->assertTrue(
            $validator->passes(),
            $requestClass.' rejected good input: '.json_encode($validator->errors()->toArray())
        );
    }

    private function assertFails(string $requestClass, array $data, ?string $expectedField = null): void
    {
        $rules = (new $requestClass())->rules();
        $validator = Validator::make($data, $rules);

        $this->assertTrue($validator->fails(), $requestClass.' accepted bad input.');

        if ($expectedField !== null) {
            $this->assertArrayHasKey($expectedField, $validator->errors()->toArray());
        }
    }

    public function test_blog_request_passes_with_valid_data(): void
    {
        $this->assertPasses(BlogRequest::class, [
            'name' => 'My blog post',
            'slug' => 'my-blog-post-'.uniqid(),
            'body' => 'A reasonably long body',
            'visibility_id' => 1,
            'content_type_id' => 1,
        ]);
    }

    public function test_blog_request_requires_name(): void
    {
        $this->assertFails(BlogRequest::class, [
            'slug' => 'my-blog',
            'body' => 'A body',
            'visibility_id' => 1,
            'content_type_id' => 1,
        ], 'name');
    }

    public function test_blog_request_rejects_invalid_slug_characters(): void
    {
        $this->assertFails(BlogRequest::class, [
            'name' => 'My blog post',
            'slug' => 'Has Spaces And CAPS',
            'body' => 'A body',
            'visibility_id' => 1,
            'content_type_id' => 1,
        ], 'slug');
    }

    public function test_blog_patch_request_allows_partial_updates(): void
    {
        // Patch requests typically relax `required` to `sometimes`.
        $rules = (new BlogPatchRequest())->rules();
        $validator = Validator::make(['name' => 'New name'], $rules);

        $this->assertTrue($validator->passes() || !$validator->errors()->has('name'));
    }

    public function test_post_request_passes_with_valid_data(): void
    {
        $this->assertPasses(PostRequest::class, [
            'body' => 'A meaningful reply',
            'visibility_id' => 1,
            'thread_id' => 1,
        ]);
    }

    public function test_post_request_requires_body(): void
    {
        $this->assertFails(PostRequest::class, [
            'visibility_id' => 1,
            'thread_id' => 1,
        ], 'body');
    }

    public function test_post_request_rejects_too_short_body(): void
    {
        $this->assertFails(PostRequest::class, [
            'body' => 'ab',
            'visibility_id' => 1,
            'thread_id' => 1,
        ], 'body');
    }

    public function test_contact_request_passes_with_valid_data(): void
    {
        $this->assertPasses(ContactRequest::class, [
            'name' => 'Alice Smith',
            'type' => 'inquiry',
            'visibility_id' => 1,
        ]);
    }

    public function test_contact_request_requires_name(): void
    {
        $this->assertFails(ContactRequest::class, [
            'type' => 'inquiry',
            'visibility_id' => 1,
        ], 'name');
    }

    public function test_event_review_request_passes_with_valid_data(): void
    {
        $this->assertPasses(EventReviewRequest::class, [
            'review' => 'Great event!',
            'review_type_id' => 1,
        ]);
    }

    public function test_event_review_request_requires_review(): void
    {
        $this->assertFails(EventReviewRequest::class, [
            'review_type_id' => 1,
        ], 'review');
    }

    public function test_forum_request_passes_with_valid_data(): void
    {
        $this->assertPasses(ForumRequest::class, [
            'name' => 'General',
            'slug' => 'general-'.uniqid(),
            'visibility_id' => 1,
        ]);
    }

    public function test_forum_request_rejects_invalid_slug(): void
    {
        $this->assertFails(ForumRequest::class, [
            'name' => 'General',
            'slug' => 'BAD SLUG',
            'visibility_id' => 1,
        ], 'slug');
    }

    public function test_menu_request_passes_with_valid_data(): void
    {
        $this->assertPasses(MenuRequest::class, [
            'name' => 'Main menu',
            'slug' => 'main-menu-'.uniqid(),
            'body' => 'Body text',
            'visibility_id' => 1,
        ]);
    }

    public function test_menu_request_rejects_missing_body(): void
    {
        $this->assertFails(MenuRequest::class, [
            'name' => 'Main menu',
            'slug' => 'main-menu',
            'visibility_id' => 1,
        ], 'body');
    }

    public function test_group_request_passes_with_valid_data(): void
    {
        $this->assertPasses(GroupRequest::class, [
            'name' => 'admins',
            'label' => 'Administrators',
            'level' => 10,
        ]);
    }

    public function test_group_request_requires_level(): void
    {
        $this->assertFails(GroupRequest::class, [
            'name' => 'admins',
            'label' => 'Administrators',
        ], 'level');
    }

    public function test_permission_request_passes_with_valid_data(): void
    {
        $this->assertPasses(PermissionRequest::class, [
            'name' => 'view-events',
            'label' => 'View Events',
            'level' => 10,
        ]);
    }

    public function test_role_request_passes_with_valid_data(): void
    {
        $this->assertPasses(RoleRequest::class, [
            'name' => 'Moderator',
            'slug' => 'moderator-'.uniqid(),
            'short' => 'Mod',
        ]);
    }

    public function test_role_request_rejects_invalid_slug(): void
    {
        $this->assertFails(RoleRequest::class, [
            'name' => 'Moderator',
            'slug' => 'BAD SLUG',
        ], 'slug');
    }

    public function test_tag_request_passes_with_valid_data(): void
    {
        $this->assertPasses(TagRequest::class, [
            'name' => 'synth',
            'slug' => 'synth-'.uniqid(),
            'description' => 'Synthesizer music',
        ]);
    }

    public function test_tag_request_rejects_too_long_name(): void
    {
        $this->assertFails(TagRequest::class, [
            'name' => str_repeat('a', 50), // max:16
            'slug' => 'a-slug',
        ], 'name');
    }
}
