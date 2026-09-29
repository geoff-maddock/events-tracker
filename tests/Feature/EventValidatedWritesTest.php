<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Series;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Event writes save validated input only, and EventRequest has a rule for
 * every field the event form sends, so nothing is dropped (#2180).
 */
class EventValidatedWritesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
    }

    /**
     * Every event attribute the create/edit form sends.
     *
     * @return array<string, mixed>
     */
    private function formPayload(string $slug): array
    {
        return [
            'name' => 'ZZ Validated Event',
            'slug' => $slug,
            'short' => 'A short ZZ',
            'description' => 'A long ZZ description',
            'event_type_id' => EventType::query()->value('id'),
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'venue_id' => Entity::factory()->create()->id,
            'promoter_id' => Entity::factory()->create()->id,
            'series_id' => Series::factory()->create()->id,
            'start_at' => '2027-03-01 20:00:00',
            'door_at' => '2027-03-01 19:00:00',
            'end_at' => '2027-03-01 23:00:00',
            'cancelled_at' => '2027-02-20 12:00:00',
            'presale_price' => '10',
            'door_price' => '12',
            'min_age' => '21',
            'do_not_repost' => '1',
            'primary_link' => 'https://example.com/zz',
            'ticket_link' => 'https://tickets.example.com/zz',
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function assertSaved(Event $event, array $payload): void
    {
        $this->assertSame('A long ZZ description', $event->description);
        $this->assertSame($payload['venue_id'], (int) $event->venue_id);
        $this->assertSame($payload['promoter_id'], (int) $event->promoter_id);
        $this->assertSame($payload['series_id'], (int) $event->series_id);
        $this->assertSame(21, (int) $event->min_age);
        $this->assertSame(1, (int) $event->do_not_repost);
        $this->assertSame('2027-02-20 12:00', $event->cancelled_at->format('Y-m-d H:i'));
        $this->assertSame('https://tickets.example.com/zz', $event->ticket_link);
    }

    public function test_creating_an_event_saves_every_form_field(): void
    {
        $payload = $this->formPayload('zz-validated-create');

        $this->actingAs($this->user)->post('/events', $payload)->assertSessionHasNoErrors();

        $event = Event::where('slug', 'zz-validated-create')->sole();
        $this->assertSaved($event, $payload);
        $this->assertSame($this->user->id, (int) $event->created_by);
    }

    public function test_updating_an_event_saves_every_form_field(): void
    {
        $event = Event::factory()->create(['created_by' => $this->user->id]);
        $payload = $this->formPayload('zz-validated-update');

        $this->actingAs($this->user)->put("/events/{$event->id}", $payload)->assertSessionHasNoErrors();

        $this->assertSaved($event->fresh(), $payload);
    }

    public function test_event_fields_are_validated(): void
    {
        $event = Event::factory()->create(['created_by' => $this->user->id]);
        $payload = ['venue_id' => 999999, 'series_id' => 999999, 'min_age' => 'adults', 'cancelled_at' => 'someday']
            + $this->formPayload('zz-validated-invalid');

        $this->actingAs($this->user)->put("/events/{$event->id}", $payload)
            ->assertSessionHasErrors(['venue_id', 'series_id', 'min_age', 'cancelled_at']);

        $this->actingAs($this->user, 'sanctum')->patchJson("/api/events/{$event->slug}", ['promoter_id' => 999999])
            ->assertStatus(422)
            ->assertJsonValidationErrors('promoter_id');
    }

    public function test_api_patch_saves_validated_fields_and_lets_an_owner_transfer_the_event(): void
    {
        $event = Event::factory()->create(['created_by' => $this->user->id]);
        $newOwner = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/events/{$event->slug}", [
                'description' => 'Patched ZZ',
                'min_age' => 18,
                'created_by' => $newOwner->id,
            ])
            ->assertOk();

        $fresh = $event->fresh();
        $this->assertSame('Patched ZZ', $fresh->description);
        $this->assertSame(18, (int) $fresh->min_age);
        $this->assertSame($newOwner->id, (int) $fresh->created_by);
    }

    public function test_event_controllers_do_not_pass_raw_request_input_to_model_writes(): void
    {
        foreach (['EventsController', 'Api/EventsController'] as $controller) {
            $source = file_get_contents(app_path("Http/Controllers/{$controller}.php"));
            $this->assertDoesNotMatchRegularExpression(
                '/(create|fill)\(\$request->(all|input)\(\)\)|\$input = \$request->(all|input)\(\);/',
                $source,
                "{$controller} passes raw request input to a model write"
            );
        }
    }
}
