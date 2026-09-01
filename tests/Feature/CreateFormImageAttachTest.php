<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\EntityType;
use App\Models\Event;
use App\Models\EventType;
use App\Models\OccurrenceType;
use App\Models\Photo;
use App\Models\Series;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use App\Services\ImageHandler;
use App\Services\TempImageStore;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The point of the image panel: an image chosen on a create form attaches to
 * the created record whether or not the user ran it through analysis.
 */
class CreateFormImageAttachTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        Storage::fake('external');
    }

    protected function tearDown(): void
    {
        foreach ((array) glob(storage_path('app/' . TempImageStore::TEMP_DIR . '/*')) as $file) {
            if (is_string($file) && is_file($file)) {
                @unlink($file);
            }
        }

        parent::tearDown();
    }

    private function activeUser(): User
    {
        /** @var User $user */
        $user = User::factory()->create([
            'email_verified_at' => Carbon::now(),
            'user_status_id' => UserStatus::ACTIVE,
        ]);

        return $user;
    }

    /** Stash an image the way the panel does, returning its token. */
    private function stashToken(): string
    {
        return (new TempImageStore(new ImageHandler()))->stash(UploadedFile::fake()->image('flyer.jpg'));
    }

    private function tempPath(string $token): string
    {
        return storage_path('app/' . TempImageStore::TEMP_DIR . '/' . $token);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function entityPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'The Smiling Moose',
            'slug' => 'the-smiling-moose',
            'short' => 'A venue',
            'description' => 'A venue in Pittsburgh.',
            'entity_type_id' => EntityType::first()->id,
            'entity_status_id' => EntityStatus::first()->id,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function seriesPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ultraviolet',
            'slug' => 'ultraviolet',
            'short' => 'A weekly night',
            'event_type_id' => EventType::first()->id,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'occurrence_type_id' => OccurrenceType::where('name', 'No Schedule')->first()->id,
        ], $overrides);
    }

    // ── Entity ───────────────────────────────────────────────────────────

    public function test_an_entity_created_with_a_token_gets_the_image_as_its_primary_photo(): void
    {
        $token = $this->stashToken();

        $this->actingAs($this->activeUser())
            ->post('/entities', $this->entityPayload(['image_temp_token' => $token]))
            ->assertSessionHasNoErrors();

        $entity = Entity::where('slug', 'the-smiling-moose')->firstOrFail();

        $this->assertCount(1, $entity->photos);
        $this->assertSame(1, (int) $entity->photos->first()->is_primary);
        $this->assertFileDoesNotExist($this->tempPath($token));
    }

    public function test_an_entity_created_without_a_token_has_no_photos(): void
    {
        $this->actingAs($this->activeUser())
            ->post('/entities', $this->entityPayload())
            ->assertSessionHasNoErrors();

        $this->assertCount(0, Entity::where('slug', 'the-smiling-moose')->firstOrFail()->photos);
    }

    public function test_a_bogus_entity_token_still_creates_the_entity(): void
    {
        $this->actingAs($this->activeUser())
            ->post('/entities', $this->entityPayload(['image_temp_token' => '../../etc/passwd']))
            ->assertSessionHasNoErrors();

        $entity = Entity::where('slug', 'the-smiling-moose')->firstOrFail();

        $this->assertCount(0, $entity->photos);
    }

    public function test_the_entity_token_is_not_mass_assigned(): void
    {
        $this->actingAs($this->activeUser())
            ->post('/entities', $this->entityPayload(['image_temp_token' => $this->stashToken()]))
            ->assertSessionHasNoErrors();

        $entity = Entity::where('slug', 'the-smiling-moose')->firstOrFail();

        $this->assertArrayNotHasKey('image_temp_token', $entity->getAttributes());
    }

    // ── Series ───────────────────────────────────────────────────────────

    public function test_a_series_created_with_a_token_gets_the_image_as_its_primary_photo(): void
    {
        $token = $this->stashToken();

        $this->actingAs($this->activeUser())
            ->post('/series', $this->seriesPayload(['image_temp_token' => $token]))
            ->assertSessionHasNoErrors();

        $series = Series::where('slug', 'ultraviolet')->firstOrFail();

        $this->assertCount(1, $series->photos);
        $this->assertSame(1, (int) $series->photos->first()->is_primary);
        $this->assertFileDoesNotExist($this->tempPath($token));
    }

    public function test_a_series_created_without_a_token_has_no_photos(): void
    {
        $this->actingAs($this->activeUser())
            ->post('/series', $this->seriesPayload())
            ->assertSessionHasNoErrors();

        $this->assertCount(0, Series::where('slug', 'ultraviolet')->firstOrFail()->photos);
    }

    /**
     * A series can be created from an existing event, which copies that event's
     * photos across. The user's own upload must still win is_primary.
     */
    public function test_an_uploaded_series_image_wins_primary_over_copied_event_photos(): void
    {
        $event = Event::factory()->create();
        $copied = Photo::factory()->create(['is_primary' => 1]);
        $event->addPhoto($copied);

        $token = $this->stashToken();

        $this->actingAs($this->activeUser())
            ->post('/series', $this->seriesPayload([
                'image_temp_token' => $token,
                'eventLinkId' => $event->id,
            ]))
            ->assertSessionHasNoErrors();

        $series = Series::where('slug', 'ultraviolet')->firstOrFail();

        $this->assertCount(2, $series->photos, 'The copied event photo and the upload should both be attached.');
        $this->assertTrue($series->photos->contains($copied->id));

        $uploaded = $series->photos->firstWhere('id', '!=', $copied->id);
        $this->assertSame(1, (int) $uploaded->is_primary);
    }

    // ── Event (regression for the picked-but-never-analyzed gap) ─────────

    public function test_an_event_created_with_a_stashed_but_unanalyzed_token_attaches_the_image(): void
    {
        $token = $this->stashToken();

        $this->actingAs($this->activeUser())
            ->post('/events', $this->eventPayload(['image_temp_token' => $token]))
            ->assertSessionHasNoErrors();

        $event = Event::where('slug', 'a-brand-new-show')->firstOrFail();

        $this->assertCount(1, $event->photos);
        $this->assertSame(1, (int) $event->photos->first()->is_primary);
    }

    public function test_an_event_still_accepts_the_legacy_flyer_token_field(): void
    {
        $token = $this->stashToken();

        $this->actingAs($this->activeUser())
            ->post('/events', $this->eventPayload(['flyer_temp_token' => $token]))
            ->assertSessionHasNoErrors();

        $this->assertCount(1, Event::where('slug', 'a-brand-new-show')->firstOrFail()->photos);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function eventPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'A Brand New Show',
            'slug' => 'a-brand-new-show',
            'short' => 'A show',
            'event_type_id' => EventType::first()->id,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => Carbon::now()->addWeek()->format('Y-m-d H:i:s'),
        ], $overrides);
    }
}
