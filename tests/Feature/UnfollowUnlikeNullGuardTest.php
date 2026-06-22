<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Post;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for the unfollow/unlike null-dereference bug.
 *
 * Previously these endpoints called ->delete() on the result of a
 * Follow/Like lookup without checking it was non-null, so hitting an
 * unfollow/unlike URL for something you were not actually following
 * (e.g. a stale link, a double-click, or a crafted request) threw a
 * 500. The fix guards the delete behind an existence check; these
 * tests lock in the graceful behaviour.
 */
class UnfollowUnlikeNullGuardTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function activeUser(): User
    {
        return User::factory()->create([
            'email_verified_at' => Carbon::now(),
            'user_status_id' => UserStatus::ACTIVE,
        ]);
    }

    /** @test */
    public function unfollowing_an_entity_you_do_not_follow_does_not_error(): void
    {
        $this->actingAs($this->activeUser());
        $entity = Entity::factory()->create();

        $response = $this->get('/entities/'.$entity->id.'/unfollow');

        $response->assertRedirect();
        $this->assertEquals(0, $entity->follows()->count());
    }

    /** @test */
    public function unfollowing_a_series_you_do_not_follow_does_not_error(): void
    {
        $this->actingAs($this->activeUser());
        $series = Series::factory()->create();

        $response = $this->get('/series/'.$series->id.'/unfollow');

        $response->assertRedirect();
    }

    /** @test */
    public function unfollowing_a_tag_you_do_not_follow_does_not_error(): void
    {
        $this->actingAs($this->activeUser());
        $tag = Tag::factory()->create();

        $response = $this->get('/tags/'.$tag->id.'/unfollow');

        $response->assertRedirect();
    }

    /** @test */
    public function unliking_a_post_you_do_not_like_does_not_error_or_decrement_likes(): void
    {
        $this->actingAs($this->activeUser());
        $post = Post::factory()->create(['likes' => 3]);

        $response = $this->get('/posts/'.$post->id.'/unlike');

        $response->assertRedirect();
        // Likes must not be decremented when no like existed.
        $this->assertEquals(3, $post->fresh()->likes);
    }
}
