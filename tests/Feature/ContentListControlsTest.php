<?php

namespace Tests\Feature;

use App\Models\Forum;
use App\Models\Photo;
use App\Models\Post;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\ThreadCategory;
use App\Models\User;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The category, forum, blog, photo, post, user and activity lists on the
 * shared list-page scaffold (#2406): what each keeps of its own.
 */
class ContentListControlsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();

        $this->admin = User::factory()->create(['name' => 'Zzlist Admin']);
        $this->admin->assignGroup('admin');
    }

    private function forum(string $name): Forum
    {
        return Forum::forceCreate([
            'name' => $name, 'slug' => strtolower($name), 'description' => $name, 'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'sort_order' => 0, 'is_active' => 1, 'created_by' => $this->admin->id,
        ]);
    }

    private function photo(string $name, bool $isEvent): Photo
    {
        return Photo::forceCreate([
            'name' => $name, 'thumbnail' => 'photos/t.jpg', 'path' => 'photos/p.jpg', 'caption' => $name,
            'is_public' => 1, 'is_primary' => 0, 'is_event' => $isEvent, 'is_approved' => 1, 'created_by' => $this->admin->id,
        ]);
    }

    public function test_a_forum_filter_is_remembered_and_reset(): void
    {
        $this->actingAs($this->admin);
        $this->forum('Zzfirstforum');
        $this->forum('Zzotherforum');

        $this->post('/forums/filter', ['filters' => ['name' => 'Zzfirst'], 'sort' => 'forums.name', 'direction' => 'asc'])
            ->assertOk()->assertSee('Zzfirstforum')->assertDontSee('Zzotherforum');
        $this->get('/forums')->assertOk()->assertSee('Zzfirstforum')->assertDontSee('Zzotherforum');
        $this->assertSame('forums.name', session('internal_forum_index/index/sort'));

        $this->get('/forums/rpp-reset')->assertRedirect(route('forums.index'));
        $this->assertSame(['name' => 'Zzfirst'], session('internal_forum_index/index/filters'));
        $this->assertNull(session('internal_forum_index/index/sort'));

        $this->get('/forums/reset')->assertRedirect(route('forums.index'));
        $this->get('/forums/filter')->assertSee('Zzfirstforum')->assertSee('Zzotherforum');
    }

    public function test_a_forum_page_lists_its_own_threads(): void
    {
        $this->actingAs($this->admin);
        $forum = $this->forum('Zzfirstforum');
        $other = $this->forum('Zzotherforum');
        $category = ThreadCategory::forceCreate(['forum_id' => $forum->id, 'name' => 'Zzcategory']);
        foreach ([[$forum, 'Zzthread here'], [$other, 'Zzthread elsewhere']] as [$in, $name]) {
            Thread::forceCreate([
                'forum_id' => $in->id, 'thread_category_id' => $category->id, 'name' => $name, 'slug' => str_replace(' ', '-', $name),
                'description' => 'd', 'body' => 'b', 'visibility_id' => Visibility::VISIBILITY_PUBLIC, 'is_active' => 1, 'created_by' => $this->admin->id,
            ]);
        }

        $this->get('/forums/'.$forum->id)->assertOk()->assertSee('Zzthread here')->assertDontSee('Zzthread elsewhere');
    }

    public function test_the_category_list_shows_ten_a_page_by_default(): void
    {
        $forum = $this->forum('Zzfirstforum');
        ThreadCategory::query()->delete();
        foreach (range(1, 12) as $i) {
            ThreadCategory::forceCreate(['forum_id' => $forum->id, 'name' => 'Zzcategory '.$i, 'created_at' => now()->subMinutes($i)]);
        }

        foreach (['/categories', '/categories/all', '/categories/filter'] as $url) {
            $this->get($url)->assertOk()->assertSee('Zzcategory 10')->assertDontSee('Zzcategory 11');
        }
    }

    public function test_the_photo_index_defaults_to_event_photos_and_the_filter_page_does_not(): void
    {
        $this->photo('Zzeventphoto', true);
        $this->photo('Zzplainphoto', false);

        $this->get('/photos')->assertOk()->assertSee('Zzeventphoto')->assertDontSee('Zzplainphoto');
        $this->get('/photos/filter')->assertOk()->assertSee('Zzeventphoto')->assertSee('Zzplainphoto');
    }

    public function test_the_post_tag_list_keeps_its_own_filters(): void
    {
        $this->actingAs($this->admin);
        $tag = Tag::factory()->create(['name' => 'Zzposttag', 'slug' => 'zzposttag']);
        $tagged = Post::factory()->create(['body' => 'Zztaggedpost', 'visibility_id' => Visibility::VISIBILITY_PUBLIC, 'created_by' => $this->admin->id]);
        $tagged->tags()->attach($tag->id);
        Post::factory()->create(['body' => 'Zzplainpost', 'visibility_id' => Visibility::VISIBILITY_PUBLIC, 'created_by' => $this->admin->id]);

        $this->get('/posts/tag/zzposttag')->assertOk()->assertSee('Zztaggedpost')->assertDontSee('Zzplainpost');
        // the tag is the page's own filter, not one saved for the main list
        $this->get('/posts')->assertOk()->assertSee('Zztaggedpost')->assertSee('Zzplainpost');
    }

    public function test_the_user_index_lists_newest_first_and_the_filter_page_by_name(): void
    {
        $this->actingAs($this->admin);
        User::factory()->create(['name' => 'Aaa Zzolder', 'created_at' => now()->subYears(30)]);
        User::factory()->create(['name' => 'Zzz Zznewer', 'created_at' => now()->addDay()]);

        $this->post('/users/filter', ['filters' => ['name' => 'Zz']])->assertOk()->assertSeeInOrder(['Aaa Zzolder', 'Zzlist Admin', 'Zzz Zznewer']);
        $this->get('/users')->assertOk()->assertSeeInOrder(['Zzz Zznewer', 'Zzlist Admin', 'Aaa Zzolder']);

        $this->get('/users/reset')->assertRedirect(route('users.index'));
        $this->assertEmpty(session('internal_user_index/index/filters'));
    }

    public function test_the_activity_list_is_for_admins_and_filters(): void
    {
        $this->actingAs(User::factory()->create())->get('/activity')->assertForbidden();

        $this->actingAs($this->admin);
        $this->post('/activity/filter', ['filters' => ['message' => 'Zzno such activity']])->assertOk();
        $this->assertSame(['message' => 'Zzno such activity'], session('internal_activity_index/index/filters'));
        $this->get('/activity/reset')->assertRedirect(route('activities.index'));
        $this->assertEmpty(session('internal_activity_index/index/filters'));
    }
}
