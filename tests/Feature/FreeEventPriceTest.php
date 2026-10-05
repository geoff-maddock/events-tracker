<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventType;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use App\Services\PriceLabel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A price of 0 is free, not "no price entered" (#2261): the setters used to
 * store 0 as null, so no event could ever be free.
 */
class FreeEventPriceTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_price_labels(): void
    {
        $this->assertNull(PriceLabel::for(null));
        $this->assertNull(PriceLabel::for(''));
        $this->assertSame('Free', PriceLabel::for('0.00'));
        $this->assertSame('Free', PriceLabel::for(0));
        $this->assertSame('$12', PriceLabel::for('12.00'));
        $this->assertSame('$12.50', PriceLabel::for('12.50'));
    }

    /**
     * @return array<string, mixed>
     */
    private function form(string $name, string $door, string $presale): array
    {
        return [
            'name' => $name, 'slug' => \Illuminate\Support\Str::slug($name), 'start_at' => Carbon::now()->addDays(3)->format('Y-m-d H:i:s'),
            'visibility_id' => Visibility::VISIBILITY_PUBLIC, 'event_type_id' => EventType::where('name', 'Concert')->value('id'),
            'door_price' => $door, 'presale_price' => $presale,
        ];
    }

    public function test_a_free_event_keeps_its_zero_price_and_a_blank_one_stays_unknown(): void
    {
        $this->withExceptionHandling();
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->actingAs($user)->post('/events', $this->form('Zz Free Show', '0', '0'))->assertSessionHasNoErrors();
        $this->actingAs($user)->post('/events', $this->form('Zz Unpriced Show', '', ''))->assertSessionHasNoErrors();

        $free = Event::where('name', 'Zz Free Show')->sole();
        $this->assertSame(0.0, (float) $free->door_price);
        $this->assertNotNull($free->door_price);
        $this->assertNotNull($free->presale_price);
        $this->assertSame('Free', $free->doorPriceLabel());

        $unpriced = Event::where('name', 'Zz Unpriced Show')->sole();
        $this->assertNull($unpriced->door_price);
        $this->assertNull($unpriced->presale_price);
        $this->assertNull($unpriced->doorPriceLabel());

        // the free calendar finds it
        $titles = collect($this->getJson('/calendar?filters[free]=1&start='.Carbon::now()->subDay()->format('Y-m-d').'&end='.Carbon::now()->addWeek()->format('Y-m-d'))->json())->pluck('title');
        $this->assertContains('Zz Free Show', $titles);
        $this->assertNotContains('Zz Unpriced Show', $titles);
    }

    public function test_the_event_page_says_free(): void
    {
        $this->withExceptionHandling();
        $event = Event::factory()->create(['door_price' => 0, 'presale_price' => null, 'visibility_id' => Visibility::VISIBILITY_PUBLIC]);

        $this->get("/events/{$event->slug}")->assertOk()->assertSee('Door: Free')->assertDontSee('Door: $0');

        // and the plain-text list
        $this->get('/events/brief-text')->assertOk()->assertSee('Free')->assertDontSee('$0');
    }

    public function test_the_tweet_caption_says_free(): void
    {
        $event = Event::factory()->create(['door_price' => 0, 'visibility_id' => Visibility::VISIBILITY_PUBLIC]);

        $this->assertStringContainsString(' Free', $event->getBriefFormat());
    }
}
