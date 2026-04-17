<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\User;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogStructuredDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_show_page_includes_rich_result_fields_in_json_ld(): void
    {
        Visibility::firstOrCreate(
            ['id' => Visibility::VISIBILITY_PUBLIC],
            ['name' => 'Public', 'label' => 'Public', 'description' => 'Public visibility']
        );

        $author = User::factory()->create(['name' => 'Schema Author']);

        $blog = Blog::factory()->create([
            'name' => 'Schema-ready blog post',
            'slug' => 'schema-ready-blog-post',
            'created_by' => $author->id,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        $response = $this->get(route('blogs.show', $blog->slug));

        $response->assertOk();
        $response->assertSee('application/ld+json', false);
        $response->assertSee('"@type": "BlogPosting"', false);
        $response->assertSee('"headline": "Schema-ready blog post"', false);
        $response->assertSee('"author"', false);
        $response->assertSee('"image"', false);
    }
}
