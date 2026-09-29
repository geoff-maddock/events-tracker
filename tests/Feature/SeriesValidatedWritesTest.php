<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\EventType;
use App\Models\Group;
use App\Models\OccurrenceType;
use App\Models\Series;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Series writes save validated input only, and the rules cover every field
 * the series form and the API send (#2180).
 */
class SeriesValidatedWritesTest extends TestCase
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

    /**
     * Every series attribute the create/edit form sends.
     *
     * @return array<string, mixed>
     */
    private function formPayload(string $slug): array
    {
        return [
            'name' => 'ZZ Validated Series',
            'slug' => $slug,
            'short' => 'A short ZZ',
            'description' => 'A long ZZ description',
            'event_type_id' => EventType::query()->value('id'),
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            // a type that needs no week/day
            'occurrence_type_id' => OccurrenceType::where('name', 'No Schedule')->value('id') ?? OccurrenceType::query()->value('id'),
            'occurrence_week_id' => 1,
            'occurrence_day_id' => 1,
            'venue_id' => Entity::factory()->create()->id,
            'promoter_id' => Entity::factory()->create()->id,
            'founded_at' => '2019-04-01 20:00',
            'start_at' => '2027-03-01 21:00',
            'door_at' => '2027-03-01 20:00',
            'end_at' => '2027-03-02 02:00',
            'soundcheck_at' => '2027-03-01 19:00',
            'cancelled_at' => '2027-06-01 12:00',
            'length' => 5,
            'min_age' => '21',
            'hold_date' => '1',
            'presale_price' => '8',
            'door_price' => '10',
            'primary_link' => 'https://example.com/zz-series',
            'ticket_link' => 'https://tickets.example.com/zz-series',
            'facebook_username' => 'zzfb',
            'instagram_username' => 'zzig',
            'twitter_username' => 'zztw',
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function assertSaved(Series $series, array $payload): void
    {
        $this->assertSame('A long ZZ description', $series->description);
        $this->assertSame($payload['venue_id'], (int) $series->venue_id);
        $this->assertSame($payload['promoter_id'], (int) $series->promoter_id);
        $this->assertSame('2019-04-01 20:00', $series->founded_at->format('Y-m-d H:i'));
        $this->assertSame('2027-06-01 12:00', $series->cancelled_at->format('Y-m-d H:i'));
        $this->assertSame(21, (int) $series->min_age);
        $this->assertTrue((bool) $series->hold_date);
        $this->assertSame('zzig', $series->instagram_username);
    }

    public function test_creating_and_updating_a_series_saves_every_form_field(): void
    {
        $payload = $this->formPayload('zz-validated-series');

        $this->actingAs($this->owner)->post('/series', $payload)->assertSessionHasNoErrors();
        $series = Series::where('slug', 'zz-validated-series')->sole();
        $this->assertSaved($series, $payload);
        $this->assertSame($this->owner->id, (int) $series->created_by);

        $payload = $this->formPayload('zz-validated-series');
        $this->actingAs($this->owner)->put("/series/{$series->slug}", $payload)->assertSessionHasNoErrors();
        $this->assertSaved($series->fresh(), $payload);
    }

    public function test_unchecking_hold_date_on_the_edit_form_clears_it(): void
    {
        $series = Series::factory()->create(['created_by' => $this->owner->id, 'hold_date' => true, 'slug' => 'zz-hold']);

        // the form sends a hidden 0 before the checkbox; an unchecked box adds nothing
        $this->actingAs($this->owner)->get("/series/{$series->slug}/edit")
            ->assertOk()
            ->assertSee('<input type="hidden" name="hold_date" value="0">', false);

        $this->actingAs($this->owner)
            ->put("/series/{$series->slug}", ['hold_date' => '0'] + $this->formPayload('zz-hold'))
            ->assertSessionHasNoErrors();

        $this->assertFalse((bool) $series->fresh()->hold_date);
    }

    public function test_series_fields_are_validated(): void
    {
        $series = Series::factory()->create(['created_by' => $this->owner->id, 'slug' => 'zz-invalid']);

        $this->actingAs($this->owner)
            ->put("/series/{$series->slug}", ['venue_id' => 999999, 'min_age' => 'adults', 'founded_at' => 'long ago'] + $this->formPayload('zz-invalid'))
            ->assertSessionHasErrors(['venue_id', 'min_age', 'founded_at']);

        $this->actingAs($this->owner, 'sanctum')
            ->patchJson("/api/series/{$series->slug}", ['twitter_username' => str_repeat('z', 65)])
            ->assertStatus(422)
            ->assertJsonValidationErrors('twitter_username');
    }

    public function test_only_an_admin_can_change_the_owner_through_the_api(): void
    {
        $series = Series::factory()->create(['created_by' => $this->owner->id, 'slug' => 'zz-owned']);
        $someone = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->actingAs($this->owner, 'sanctum')
            ->patchJson("/api/series/{$series->slug}", ['created_by' => $someone->id, 'short' => 'Owner patch ZZ'])
            ->assertOk();
        $this->assertSame($this->owner->id, (int) $series->fresh()->created_by);
        $this->assertSame('Owner patch ZZ', $series->fresh()->short);

        $admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $admin->groups()->attach(Group::firstOrCreate(['name' => 'admin'])->id);
        $series->forceFill(['created_by' => $admin->id])->save();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/series/{$series->slug}", ['created_by' => $someone->id])
            ->assertOk();
        $this->assertSame($someone->id, (int) $series->fresh()->created_by);
    }

    public function test_api_put_ignores_fields_without_a_column(): void
    {
        $series = Series::factory()->create(['created_by' => $this->owner->id, 'slug' => 'zz-put']);

        $this->actingAs($this->owner, 'sanctum')
            ->putJson("/api/series/{$series->slug}", ['benefit_id' => 3, 'location_id' => 4] + $this->formPayload('zz-put'))
            ->assertOk();

        $this->assertSame('ZZ Validated Series', $series->fresh()->name);
    }

    public function test_series_controllers_do_not_pass_raw_request_input_to_model_writes(): void
    {
        foreach (['SeriesController', 'Api/SeriesController'] as $controller) {
            $source = file_get_contents(app_path("Http/Controllers/{$controller}.php"));
            $this->assertDoesNotMatchRegularExpression(
                '/(create|fill|update)\(\$request->(all|input)\(\)\)|\$input = \$request->(all|input)\(\);/',
                $source,
                "{$controller} passes raw request input to a model write"
            );
        }
    }
}
