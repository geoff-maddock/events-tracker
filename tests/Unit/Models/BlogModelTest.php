<?php

namespace Tests\Unit\Models;

use App\Models\Blog;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogModelTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_owned_by_returns_true_for_creator(): void
    {
        $user = User::factory()->create();
        $blog = Blog::factory()->create(['created_by' => $user->id]);

        $this->assertTrue($blog->ownedBy($user));
    }

    public function test_owned_by_returns_false_for_non_creator(): void
    {
        $blog = Blog::factory()->create();
        $other = User::factory()->create();

        $this->assertFalse($blog->ownedBy($other));
    }

    public function test_path_returns_blog_url_path(): void
    {
        // Note: Blog::path() returns `/blog/<id>` (singular), not `/blogs/<slug>`.
        $blog = Blog::factory()->create();

        $this->assertSame('/blog/'.$blog->id, $blog->path());
    }

    public function test_tag_list_attribute_returns_attached_tag_ids(): void
    {
        $blog = Blog::factory()->create();
        $tag = Tag::factory()->create();
        $blog->tags()->attach($tag->id);

        $this->assertSame([$tag->id], $blog->fresh()->tag_list);
    }

    public function test_is_recent_returns_true_for_freshly_created_blog(): void
    {
        $blog = Blog::factory()->create();

        $this->assertTrue($blog->isRecent());
    }

    public function test_is_recent_returns_false_for_old_blog(): void
    {
        $blog = Blog::factory()->create();
        $blog->forceFill(['created_at' => Carbon::now()->subDays(60)])->save();

        $this->assertFalse($blog->fresh()->isRecent());
    }
}
