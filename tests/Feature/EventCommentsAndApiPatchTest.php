<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Event;
use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Two bugs the write-route authorization matrix turned up (#2186).
 */
class EventCommentsAndApiPatchTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $author;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->author = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
    }

    private function eventComment(): Comment
    {
        $event = Event::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        // Comment stamps created_by from the signed-in user
        $this->actingAs($this->author);

        return $event->comments()->create(['message' => 'An event comment ZZ']);
    }

    public function test_an_event_comment_can_be_edited_and_deleted_by_its_author(): void
    {
        // CommentsController::update/destroy only took an Entity, so for
        // events/{event}/comments/{comment} the arguments landed in the wrong
        // slots and every call was a 500
        $comment = $this->eventComment();
        /** @var Event $event */
        $event = $comment->commentable;

        $this->put("/events/{$event->slug}/comments/{$comment->id}", ['message' => 'Edited event comment ZZ'])
            ->assertRedirect(route('events.show', $event->getRouteKey()));
        $this->assertSame('Edited event comment ZZ', $comment->fresh()->message);

        $this->get("/events/{$event->slug}/comments/{$comment->id}/edit")->assertOk();

        $this->delete("/events/{$event->slug}/comments/{$comment->id}")->assertRedirect();
        $this->assertNull(Comment::find($comment->id));
    }

    public function test_another_user_cannot_edit_an_event_comment(): void
    {
        $comment = $this->eventComment();
        /** @var Event $event */
        $event = $comment->commentable;

        $this->actingAs(User::factory()->create(['user_status_id' => UserStatus::ACTIVE]))
            ->put("/events/{$event->slug}/comments/{$comment->id}", ['message' => 'Hijacked ZZ'])
            ->assertForbidden();

        $this->assertSame('An event comment ZZ', $comment->fresh()->message);
    }

    public function test_a_non_owners_api_patch_is_refused_with_403_not_an_error(): void
    {
        // unauthorized() was typed to the PUT FormRequest, so the PATCH request
        // passed to it was a TypeError (500) instead of a refusal
        $this->actingAs($this->author);
        $thread = Thread::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        $post = Post::factory()->create(['thread_id' => $thread->id, 'created_by' => $this->author->id]);

        $stranger = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->actingAs($stranger, 'sanctum');

        $this->patchJson("/api/posts/{$post->id}", ['body' => 'Hijacked ZZ'])->assertForbidden();
        $this->patchJson("/api/threads/{$thread->id}", ['name' => 'Hijacked ZZ'])->assertForbidden();
    }
}
