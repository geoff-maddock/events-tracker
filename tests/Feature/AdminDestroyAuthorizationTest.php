<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\EntityType;
use App\Models\Event;
use App\Models\EventReview;
use App\Models\Forum;
use App\Models\Group;
use App\Models\Menu;
use App\Models\Permission;
use App\Models\Post;
use App\Models\ReviewType;
use App\Models\ThreadCategory;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Web destroy routes for site configuration that any visitor or signed-in
 * user could reach, plus destroy actions that failed with a 500 for guests
 * and a review delete that never deleted anything (#2138).
 *
 * The primary signal is that the row survives a denied request.
 */
class AdminDestroyAuthorizationTest extends TestCase
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

        return $user;
    }

    // Groups, permissions, menus, entity types: admin only

    public function test_guest_cannot_destroy_a_group(): void
    {
        $group = Group::factory()->create();

        $this->delete(route('groups.destroy', $group))->assertRedirect();

        $this->assertDatabaseHas('groups', ['id' => $group->id]);
    }

    public function test_member_cannot_destroy_a_group(): void
    {
        $group = Group::factory()->create();

        $this->actingAs($this->makeUser())
            ->delete(route('groups.destroy', $group))
            ->assertForbidden();

        $this->assertDatabaseHas('groups', ['id' => $group->id]);
    }

    public function test_admin_can_destroy_a_group(): void
    {
        $group = Group::factory()->create();

        $this->actingAs($this->makeUser('admin'))
            ->delete(route('groups.destroy', $group))
            ->assertRedirect();

        $this->assertDatabaseMissing('groups', ['id' => $group->id]);
    }

    public function test_member_cannot_destroy_a_permission(): void
    {
        $permission = new Permission();
        $permission->forceFill([
            'name' => 'zz_permission',
            'label' => 'ZZ Permission',
            'description' => 'ZZ',
            'level' => 0,
        ])->save();

        $this->delete(route('permissions.destroy', $permission))->assertRedirect();
        $this->actingAs($this->makeUser())
            ->delete(route('permissions.destroy', $permission))
            ->assertForbidden();

        $this->assertDatabaseHas('permissions', ['id' => $permission->id]);
    }

    public function test_member_cannot_destroy_a_menu(): void
    {
        $menu = Menu::factory()->create();

        $this->delete(route('menus.destroy', $menu))->assertRedirect();
        $this->actingAs($this->makeUser())
            ->delete(route('menus.destroy', $menu))
            ->assertForbidden();

        $this->assertDatabaseHas('menus', ['id' => $menu->id]);
    }

    public function test_admin_can_destroy_a_menu(): void
    {
        $menu = Menu::factory()->create();

        $this->actingAs($this->makeUser('admin'))
            ->delete(route('menus.destroy', $menu))
            ->assertRedirect();

        $this->assertDatabaseMissing('menus', ['id' => $menu->id]);
    }

    public function test_member_cannot_destroy_an_entity_type(): void
    {
        $entityType = new EntityType();
        $entityType->forceFill(['name' => 'ZZ Type', 'slug' => 'zz-type', 'short' => 'ZZ'])->save();

        $this->actingAs($this->makeUser())
            ->delete(route('entity-types.destroy', $entityType))
            ->assertForbidden();

        $this->assertDatabaseHas('entity_types', ['id' => $entityType->id]);
    }

    // Blogs, posts, categories: guests are redirected instead of erroring

    public function test_guest_is_redirected_from_blog_destroy(): void
    {
        $blog = Blog::factory()->create(['created_by' => $this->makeUser()->id]);

        $this->delete(route('blogs.destroy', $blog))->assertRedirect();

        $this->assertDatabaseHas('blogs', ['id' => $blog->id]);
    }

    public function test_guest_is_redirected_from_post_destroy(): void
    {
        $post = Post::factory()->create();

        $this->delete(route('posts.destroy', $post))->assertRedirect();

        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    public function test_guest_is_redirected_from_category_destroy(): void
    {
        $category = ThreadCategory::factory()->create(['forum_id' => Forum::factory()->create()->id]);

        $this->delete(route('categories.destroy', $category))->assertRedirect();

        $this->assertDatabaseHas('thread_categories', ['id' => $category->id]);
    }

    // Event reviews

    private function reviewBy(User $author): EventReview
    {
        $review = new EventReview();
        $review->forceFill([
            'event_id' => Event::factory()->create()->id,
            'user_id' => $author->id,
            'review_type_id' => ReviewType::query()->value('id') ?? ReviewType::factory()->create()->id,
            'attended' => 0,
            'review' => 'Review ZZ',
        ])->save();

        return $review;
    }

    public function test_guest_cannot_destroy_a_review(): void
    {
        $review = $this->reviewBy($this->makeUser());

        $this->delete(route('events.reviews.destroy', [$review->event, $review]))->assertRedirect();

        $this->assertDatabaseHas('event_reviews', ['id' => $review->id]);
    }

    public function test_non_author_cannot_destroy_a_review(): void
    {
        $review = $this->reviewBy($this->makeUser());

        $this->actingAs($this->makeUser())
            ->delete(route('events.reviews.destroy', [$review->event, $review]))
            ->assertForbidden();

        $this->assertDatabaseHas('event_reviews', ['id' => $review->id]);
    }

    public function test_author_can_destroy_their_review(): void
    {
        $author = $this->makeUser();
        $review = $this->reviewBy($author);

        $this->actingAs($author)
            ->delete(route('events.reviews.destroy', [$review->event, $review]))
            ->assertRedirect();

        $this->assertDatabaseMissing('event_reviews', ['id' => $review->id]);
    }

    public function test_reviews_destroy_route_no_longer_deletes_events(): void
    {
        $event = Event::factory()->create();

        $this->actingAs($this->makeUser('admin'))->delete('/reviews/'.$event->id);

        $this->assertDatabaseHas('events', ['id' => $event->id]);
    }
}
