<?php

namespace Tests\Feature;

use App\Models\Like;
use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class LikesTest extends TestCase
{
    // refresh database and run migrations before test
    use RefreshDatabase;

    // reseed the database
    protected $seed = true;

    #[Test]
    public function a_guest_cannot_like_anything()
    {
        $this->withExceptionHandling()
            ->post('/posts/1/like')
            ->assertRedirect('/');
    }

    #[Test]
    public function an_authenticated_user_can_like_a_post()
    {
        $this->signIn();

        $post = Post::factory()->create();
        // the likes column, not the likes() relation it shares a name with
        $likes = (int) $post->getAttribute('likes');

        $this->post('/posts/' . $post->id . '/like');

        $post->refresh();

        $this->assertEquals($likes + 1, $post->getAttribute('likes'));
    }

    #[Test]
    public function an_authenticated_user_can_like_a_thread()
    {
        $this->signIn();

        $thread = Thread::factory()->create();
        $likes = (int) $thread->getAttribute('likes');

        $this->post('/threads/' . $thread->id . '/like');

        $thread->refresh();

        $this->assertEquals($likes + 1, $thread->getAttribute('likes'));
    }

    public function test_liking_twice_counts_once_and_unliking_takes_it_back(): void
    {
        $this->withExceptionHandling();
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $thread = Thread::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC, 'likes' => 0]);
        $post = Post::factory()->create(['thread_id' => $thread->id, 'likes' => 0]);

        foreach (['threads' => $thread, 'posts' => $post] as $path => $model) {
            $type = $path === 'threads' ? 'thread' : 'post';
            $this->actingAs($user)->post("/{$path}/{$model->id}/like");
            $this->actingAs($user)->post("/{$path}/{$model->id}/like");

            $this->assertSame(1, Like::where(['object_type' => $type, 'object_id' => $model->id])->count(), $path);
            $this->assertSame(1, (int) $model->fresh()->getAttribute('likes'), $path);

            $this->actingAs($user)->post("/{$path}/{$model->id}/unlike");
            $this->actingAs($user)->post("/{$path}/{$model->id}/unlike");

            $this->assertSame(0, Like::where(['object_type' => $type, 'object_id' => $model->id])->count(), $path);
            $this->assertSame(0, (int) $model->fresh()->getAttribute('likes'), $path);
        }
    }

    public function test_likes_follow_the_thread_page_rule(): void
    {
        $this->withExceptionHandling();
        $owner = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $other = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        // created while signed in as the owner: the model stamps created_by itself
        $thread = $this->actingAs($owner)->thread();
        $post = Post::factory()->create(['thread_id' => $thread->id]);

        $this->actingAs($other)->post("/threads/{$thread->id}/like")
            ->assertSessionHas('flash_message.message', 'No such thread');
        $this->actingAs($other)->post("/posts/{$post->id}/like")
            ->assertSessionHas('flash_message.message', 'No such post');
        $this->assertSame(0, Like::count());

        $this->actingAs($owner)->post("/threads/{$thread->id}/like");
        $this->actingAs($owner)->post("/posts/{$post->id}/like");
        $this->assertSame(2, Like::count());
    }

    private function thread(): Thread
    {
        return Thread::factory()->create(['name' => 'ZZ Own Notes', 'visibility_id' => Visibility::VISIBILITY_PRIVATE]);
    }

    public function test_the_table_refuses_a_second_like_from_the_same_user(): void
    {
        $user = User::factory()->create();
        $row = ['object_type' => 'thread', 'object_id' => 1, 'user_id' => $user->id];
        Like::create($row);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        Like::create($row);
    }
}
