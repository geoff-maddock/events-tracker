<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Event;
use App\Models\Series;
use App\Models\ClickTrack;
use App\Models\User;
use App\Models\EventType;
use App\Models\Visibility;

class ClickTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /** @test */
    public function event_with_ticket_link_redirects_and_tracks_click()
    {
        // Create an event with a ticket link
        $event = Event::factory()->create([
            'ticket_link' => 'https://example.com/tickets',
        ]);

        // Visit the tracking URL
        $response = $this->get('/go/evt-' . $event->id);

        // Assert redirect
        $response->assertRedirect();

        // Assert click was tracked
        $this->assertDatabaseHas('click_tracks', [
            'event_id' => $event->id,
        ]);
    }

    /** @test */
    public function event_without_ticket_link_redirects_to_event_page()
    {
        // Create an event without a ticket link
        $event = Event::factory()->create([
            'ticket_link' => null,
        ]);

        // Visit the tracking URL
        $response = $this->get('/go/evt-' . $event->id);

        // Assert redirect to event page
        $response->assertRedirect(route('events.show', $event->id));

        // Assert no click was tracked
        $this->assertDatabaseMissing('click_tracks', [
            'event_id' => $event->id,
        ]);
    }

    /** @test */
    public function nonexistent_event_redirects_to_home()
    {
        // Visit the tracking URL for non-existent event
        $response = $this->get('/go/evt-99999');

        // Assert redirect to home
        $response->assertRedirect(route('home'));

        // Assert no click was tracked
        $this->assertEquals(0, ClickTrack::count());
    }

    /** @test */
    public function series_with_ticket_link_redirects_and_tracks_click()
    {
        // Create a series with a ticket link
        $series = Series::factory()->create([
            'ticket_link' => 'https://example.com/tickets',
        ]);

        // Visit the tracking URL
        $response = $this->get('/go/ser-' . $series->id);

        // Assert redirect
        $response->assertRedirect();

        // Assert click was tracked
        $this->assertDatabaseHas('click_tracks', [
            'event_id' => null,
        ]);
    }

    /** @test */
    public function click_tracking_stores_user_agent_and_referrer()
    {
        // Create an event with a ticket link
        $event = Event::factory()->create([
            'ticket_link' => 'https://example.com/tickets',
        ]);

        // Visit the tracking URL with custom headers
        $response = $this->withHeaders([
            'User-Agent' => 'TestBrowser/1.0',
            'Referer' => 'https://example.com/events',
        ])->get('/go/evt-' . $event->id);

        // Assert click was tracked with proper data
        $this->assertDatabaseHas('click_tracks', [
            'event_id' => $event->id,
            'user_agent' => 'TestBrowser/1.0',
            'referrer' => 'https://example.com/events',
        ]);
    }

    /** @test */
    public function click_tracking_attaches_referral_params()
    {
        // Create an event with a ticket link
        $event = Event::factory()->create([
            'ticket_link' => 'https://example.com/tickets',
        ]);

        // Visit the tracking URL
        $response = $this->get('/go/evt-' . $event->id);

        // Get the redirect location
        $redirectUrl = $response->headers->get('Location');

        // Assert referral parameter was added
        $this->assertStringContainsString('ref=', $redirectUrl);
    }

    /** @test */
    public function event_ticket_tracking_link_method_returns_correct_url()
    {
        // Create an event with a ticket link
        $event = Event::factory()->create([
            'ticket_link' => 'https://example.com/tickets',
        ]);

        // Get the tracking link
        $trackingLink = $event->getTicketTrackingLink();

        // Assert it returns the correct URL
        $this->assertEquals(route('clicktrack.event', ['id' => $event->id]), $trackingLink);
    }

    /** @test */
    public function event_without_ticket_link_returns_null_tracking_link()
    {
        // Create an event without a ticket link
        $event = Event::factory()->create([
            'ticket_link' => null,
        ]);

        // Get the tracking link
        $trackingLink = $event->getTicketTrackingLink();

        // Assert it returns null
        $this->assertNull($trackingLink);
    }

    /** @test */
    public function series_ticket_tracking_link_method_returns_correct_url()
    {
        // Create a series with a ticket link
        $series = Series::factory()->create([
            'ticket_link' => 'https://example.com/tickets',
        ]);

        // Get the tracking link
        $trackingLink = $series->getTicketTrackingLink();

        // Assert it returns the correct URL
        $this->assertEquals(route('clicktrack.series', ['id' => $series->id]), $trackingLink);
    }
}
