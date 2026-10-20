<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventReview;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The nested review URL and the attending export used to 500: the first rendered
 * the event page without its data, the second ran for guests with no user (#2336).
 */
class ReviewShowAndExportAttendingTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();

        $this->owner = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
    }

    private function reviewOn(Event $event): EventReview
    {
        return EventReview::create(['event_id' => $event->id, 'user_id' => $this->owner->id, 'review_type_id' => 1, 'review' => 'A ZZ review', 'attended' => 1]);
    }

    public function test_nested_review_url_redirects_to_the_review_page(): void
    {
        $event = Event::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC, 'created_by' => $this->owner->id]);
        $review = $this->reviewOn($event);

        $this->get('/events/'.$event->id.'/reviews/'.$review->id)
            ->assertRedirect(route('reviews.show', $review->id));
    }

    public function test_nested_review_url_404s_for_a_review_of_another_event(): void
    {
        $event = Event::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC, 'created_by' => $this->owner->id]);
        $other = Event::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC, 'created_by' => $this->owner->id]);
        $review = $this->reviewOn($other);

        $this->get('/events/'.$event->id.'/reviews/'.$review->id)->assertNotFound();
    }

    public function test_nested_review_url_404s_for_an_event_the_guest_cannot_see(): void
    {
        $event = Event::factory()->create(['visibility_id' => Visibility::VISIBILITY_PRIVATE, 'created_by' => $this->owner->id]);
        $review = $this->reviewOn($event);

        $this->get('/events/'.$event->id.'/reviews/'.$review->id)->assertNotFound();
    }

    public function test_export_attending_sends_guests_to_login(): void
    {
        $this->get('/events/export-attending')->assertRedirect(route('login'));
    }

    public function test_export_attending_renders_for_a_signed_in_user(): void
    {
        $this->actingAs($this->owner)->get('/events/export-attending')->assertOk();
    }
}
