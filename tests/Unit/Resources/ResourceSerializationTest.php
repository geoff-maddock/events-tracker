<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\BlogResource;
use App\Http\Resources\ForumResource;
use App\Http\Resources\LinkResource;
use App\Http\Resources\MenuResource;
use App\Http\Resources\MinimalUserResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\SeriesResource;
use App\Models\Blog;
use App\Models\Entity;
use App\Models\Forum;
use App\Models\Link;
use App\Models\Menu;
use App\Models\Post;
use App\Models\Series;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Verify each Resource's toArray() produces the expected key set when fed
 * a fresh model. These are deterministic transformations so we only check
 * structure, not values.
 */
class ResourceSerializationTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function request(): Request
    {
        return Request::create('/', 'GET');
    }

    public function test_blog_resource_exposes_expected_keys(): void
    {
        $blog = Blog::factory()->create();
        $data = (new BlogResource($blog))->toArray($this->request());

        $this->assertEquals(
            ['id', 'name', 'slug', 'visibility_id', 'content_type_id', 'body', 'menu_id', 'sort_order', 'created_at', 'updated_at'],
            array_keys($data)
        );
        $this->assertEquals($blog->id, $data['id']);
    }

    public function test_link_resource_exposes_expected_keys(): void
    {
        // LinkFactory is broken; build directly.
        $link = Link::create([
            'url' => 'https://example.com',
            'text' => 'Example',
            'title' => 'Example',
        ]);

        $data = (new LinkResource($link))->toArray($this->request());

        $this->assertEquals(
            ['id', 'url', 'text', 'image', 'api', 'title', 'confirm', 'is_primary', 'created_at', 'updated_at'],
            array_keys($data)
        );
        $this->assertEquals('https://example.com', $data['url']);
    }

    public function test_minimal_user_resource_exposes_id_and_username(): void
    {
        $user = User::factory()->create();

        $data = (new MinimalUserResource($user))->toArray($this->request());

        $this->assertEquals(['id', 'username'], array_keys($data));
        $this->assertEquals($user->id, $data['id']);
        $this->assertEquals($user->name, $data['username']);
    }

    public function test_post_resource_exposes_expected_keys(): void
    {
        $post = Post::factory()->create();

        $data = (new PostResource($post))->toArray($this->request());

        $expected = [
            'id', 'thread_id', 'thread_name', 'name', 'slug', 'description',
            'body', 'allow_html', 'content_type_id', 'visibility_id', 'views',
            'is_active', 'author', 'created_at', 'updated_at',
        ];
        $this->assertEquals($expected, array_keys($data));
    }

    public function test_forum_resource_exposes_expected_keys(): void
    {
        $forum = Forum::factory()->create();

        $data = (new ForumResource($forum))->toArray($this->request());

        $expected = ['id', 'name', 'slug', 'description', 'visibility', 'threads_count', 'created_at', 'updated_at'];
        $this->assertEquals($expected, array_keys($data));
    }

    public function test_menu_resource_exposes_expected_keys(): void
    {
        // MenuFactory is self-referential; build directly.
        $menu = Menu::create([
            'name' => 'Test menu',
            'slug' => 'test-menu-'.uniqid(),
            'body' => 'Body',
            'visibility_id' => 1,
        ]);

        $data = (new MenuResource($menu))->toArray($this->request());

        $expected = ['id', 'name', 'slug', 'body', 'menu_parent_id', 'visibility_id', 'created_at', 'updated_at'];
        $this->assertEquals($expected, array_keys($data));
    }

    public function test_series_resource_returns_an_array_with_core_keys(): void
    {
        $series = Series::factory()->create();

        $data = (new SeriesResource($series))->toArray($this->request());

        // Only assert a subset of stable top-level keys — the resource
        // has 40+ keys and we don't want to brittle-test all of them.
        foreach (['id', 'name', 'slug', 'description', 'start_at', 'end_at'] as $key) {
            $this->assertArrayHasKey($key, $data, "SeriesResource missing key: $key");
        }
    }
}
