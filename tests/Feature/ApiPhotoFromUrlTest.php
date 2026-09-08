<?php

namespace Tests\Feature;

use App\Mail\FollowingUpdate;
use App\Models\Entity;
use App\Models\Event;
use App\Models\Follow;
use App\Models\Photo;
use App\Models\Profile;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserStatus;
use App\Services\RemoteImageFetcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * POST /api/{events,entities,series}/{id}/photos/from-url (issue #2123):
 * attach a photo the server downloads from a public https URL. Must behave
 * exactly like the multipart upload apart from where the bytes come from.
 */
class ApiPhotoFromUrlTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $owner;
    private User $attacker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        Storage::fake('external');
        Mail::fake();

        // no DNS in the sandbox: cdn.example.com is public, internal.example.com is loopback
        $this->app->bind(RemoteImageFetcher::class, fn () => new RemoteImageFetcher(fn (string $host) => match ($host) {
            'cdn.example.com' => ['93.184.216.34'],
            'internal.example.com' => ['127.0.0.1'],
            default => [],
        }));

        $this->owner = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->attacker = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
    }

    private function png(): string
    {
        $img = imagecreatetruecolor(8, 8);
        ob_start();
        imagepng($img);

        return (string) ob_get_clean();
    }

    private function fakeCdn(): void
    {
        Http::fake(['https://cdn.example.com/*' => Http::response($this->png(), 200, ['Content-Type' => 'image/png'])]);
    }

    private function postUrl(string $path, ?string $url, ?User $as = null): \Illuminate\Testing\TestResponse
    {
        $this->actingAs($as ?? $this->owner, 'sanctum');

        return $this->postJson($path, $url === null ? [] : ['url' => $url]);
    }

    private function existingPhoto(): Photo
    {
        return Photo::factory()->create([
            'name' => 'existing.webp',
            'path' => 'photos/existing.webp',
            'thumbnail' => 'photos/tn-existing.webp',
            'is_primary' => 1,
            'created_by' => $this->owner->id,
            'updated_by' => null,
        ]);
    }

    private function followerOfTag(Tag $tag): User
    {
        $follower = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        Profile::factory()->create(['user_id' => $follower->id, 'setting_instant_update' => 1]);
        Follow::create(['user_id' => $follower->id, 'object_type' => 'tag', 'object_id' => $tag->id]);

        return $follower;
    }

    // ---- events -----------------------------------------------------------

    /** @test */
    public function event_first_photo_from_url_is_attached_primary_and_notifies_followers(): void
    {
        $this->fakeCdn();

        $tag = Tag::factory()->create();
        $event = Event::factory()->create(['created_by' => $this->owner->id, 'start_at' => now()->addDays(2)]);
        $event->tags()->attach($tag->id);
        $follower = $this->followerOfTag($tag);

        $response = $this->postUrl('/api/events/'.$event->id.'/photos/from-url', 'https://cdn.example.com/flyers/Show%20Flyer.png?v=2');

        $response->assertStatus(201)
            ->assertJsonPath('is_primary', 1);

        $photo = $event->photos()->first();
        $this->assertNotNull($photo);
        $this->assertSame(1, $photo->is_primary);
        $this->assertSame($this->owner->id, $photo->created_by);
        $this->assertStringEndsWith('_Show-Flyer.webp', $photo->name);
        $this->assertSame($response->json('id'), $photo->id);

        Storage::disk('external')->assertExists($photo->path);
        Storage::disk('external')->assertExists($photo->thumbnail);

        $this->assertSame(1, Mail::sent(FollowingUpdate::class)->filter(fn (FollowingUpdate $m) => $m->hasTo($follower->email))->count());
    }

    /** @test */
    public function event_second_photo_from_url_is_not_primary_and_does_not_renotify(): void
    {
        $this->fakeCdn();

        $tag = Tag::factory()->create();
        $event = Event::factory()->create(['created_by' => $this->owner->id, 'start_at' => now()->addDays(2)]);
        $event->tags()->attach($tag->id);
        $event->addPhoto($this->existingPhoto());
        $this->followerOfTag($tag);

        $this->postUrl('/api/events/'.$event->id.'/photos/from-url', 'https://cdn.example.com/second.png')
            ->assertStatus(201)
            ->assertJsonPath('is_primary', fn ($v) => !$v);

        $this->assertSame(2, $event->photos()->count());
        $this->assertSame(1, $event->photos()->where('photos.is_primary', 1)->count());
        Mail::assertNothingSent();
    }

    /** @test */
    public function event_from_url_rejects_non_owner(): void
    {
        $this->fakeCdn();
        $event = Event::factory()->create(['created_by' => $this->owner->id]);

        $this->postUrl('/api/events/'.$event->id.'/photos/from-url', 'https://cdn.example.com/a.png', $this->attacker)
            ->assertStatus(403);

        $this->assertSame(0, $event->photos()->count());
        Http::assertNothingSent();
    }

    /** @test */
    public function event_from_url_requires_authentication(): void
    {
        $this->fakeCdn();
        $event = Event::factory()->create(['created_by' => $this->owner->id]);

        $this->postJson('/api/events/'.$event->id.'/photos/from-url', ['url' => 'https://cdn.example.com/a.png'])
            ->assertStatus(401);

        Http::assertNothingSent();
    }

    /** @test */
    public function event_from_url_returns_404_for_an_unknown_event(): void
    {
        $this->fakeCdn();

        $this->postUrl('/api/events/999999/photos/from-url', 'https://cdn.example.com/a.png')
            ->assertStatus(404);

        Http::assertNothingSent();
    }

    /** @test */
    public function event_from_url_validates_the_url_field(): void
    {
        $this->fakeCdn();
        $event = Event::factory()->create(['created_by' => $this->owner->id]);

        $this->postUrl('/api/events/'.$event->id.'/photos/from-url', null)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['url']);

        $this->postUrl('/api/events/'.$event->id.'/photos/from-url', 'not a url')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['url']);

        Http::assertNothingSent();
    }

    /**
     * @test
     *
     * @dataProvider unsafeUrls
     */
    public function event_from_url_returns_422_for_unsafe_or_invalid_sources(string $url, string $message): void
    {
        Http::fake([
            'https://cdn.example.com/redirect-private' => Http::response('', 302, ['Location' => 'https://internal.example.com/secret.png']),
            'https://cdn.example.com/huge.jpg' => Http::response(str_repeat('x', RemoteImageFetcher::MAX_BYTES + 1), 200, ['Content-Type' => 'image/jpeg']),
            'https://cdn.example.com/page.jpg' => Http::response('<html><body>hi</body></html>', 200, ['Content-Type' => 'image/jpeg']),
            'https://internal.example.com/*' => Http::response($this->png(), 200, ['Content-Type' => 'image/png']),
        ]);

        $event = Event::factory()->create(['created_by' => $this->owner->id]);

        $this->postUrl('/api/events/'.$event->id.'/photos/from-url', $url)
            ->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_contains($m, $message));

        $this->assertSame(0, $event->photos()->count());
        $this->assertSame(0, Photo::count());
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'internal.example.com'));
    }

    public static function unsafeUrls(): array
    {
        return [
            'http scheme' => ['http://cdn.example.com/a.png', 'Only https'],
            'loopback literal' => ['https://127.0.0.1/a.png', 'public host'],
            'host resolving to loopback' => ['https://internal.example.com/a.png', 'public host'],
            'redirect to private' => ['https://cdn.example.com/redirect-private', 'public host'],
            'over 5 MB' => ['https://cdn.example.com/huge.jpg', '5 MB limit'],
            'html served as jpeg' => ['https://cdn.example.com/page.jpg', 'supported image'],
        ];
    }

    /** @test */
    public function event_from_url_is_rate_limited(): void
    {
        $this->fakeCdn();
        $event = Event::factory()->create(['created_by' => $this->owner->id]);

        $this->postUrl('/api/events/'.$event->id.'/photos/from-url', 'https://cdn.example.com/a.png')
            ->assertStatus(201)
            ->assertHeader('X-RateLimit-Limit', '20');
    }

    // ---- entities ---------------------------------------------------------

    /** @test */
    public function entity_photo_from_url_is_attached(): void
    {
        $this->fakeCdn();
        $entity = Entity::factory()->create(['created_by' => $this->owner->id]);

        $this->postUrl('/api/entities/'.$entity->id.'/photos/from-url', 'https://cdn.example.com/venue.png')
            ->assertStatus(201)
            ->assertJsonPath('is_primary', 1);

        $photo = $entity->photos()->first();
        $this->assertNotNull($photo);
        $this->assertSame($this->owner->id, $photo->created_by);
        Storage::disk('external')->assertExists($photo->path);

        // a second one is not primary
        $this->postUrl('/api/entities/'.$entity->id.'/photos/from-url', 'https://cdn.example.com/venue-2.png')
            ->assertStatus(201)
            ->assertJsonPath('is_primary', fn ($v) => !$v);
        $this->assertSame(2, $entity->photos()->count());
    }

    /** @test */
    public function entity_from_url_rejects_non_owner_and_unknown_ids(): void
    {
        $this->fakeCdn();
        $entity = Entity::factory()->create(['created_by' => $this->owner->id]);

        $this->postUrl('/api/entities/'.$entity->id.'/photos/from-url', 'https://cdn.example.com/a.png', $this->attacker)
            ->assertStatus(403);
        $this->postUrl('/api/entities/999999/photos/from-url', 'https://cdn.example.com/a.png')
            ->assertStatus(404);
        $this->postUrl('/api/entities/'.$entity->id.'/photos/from-url', 'https://internal.example.com/a.png')
            ->assertStatus(422);

        $this->assertSame(0, $entity->photos()->count());
        Http::assertNothingSent();
    }

    // ---- series -----------------------------------------------------------

    /** @test */
    public function series_photo_from_url_is_attached(): void
    {
        $this->fakeCdn();
        $series = Series::factory()->create(['created_by' => $this->owner->id]);

        $this->postUrl('/api/series/'.$series->id.'/photos/from-url', 'https://cdn.example.com/series.png')
            ->assertStatus(201)
            ->assertJsonPath('is_primary', 1);

        $photo = $series->photos()->first();
        $this->assertNotNull($photo);
        $this->assertSame($this->owner->id, $photo->created_by);
        Storage::disk('external')->assertExists($photo->path);

        $this->postUrl('/api/series/'.$series->id.'/photos/from-url', 'https://cdn.example.com/series-2.png')
            ->assertStatus(201)
            ->assertJsonPath('is_primary', fn ($v) => !$v);
        $this->assertSame(2, $series->photos()->count());
    }

    /** @test */
    public function series_from_url_rejects_non_owner_and_unknown_ids(): void
    {
        $this->fakeCdn();
        $series = Series::factory()->create(['created_by' => $this->owner->id]);

        $this->postUrl('/api/series/'.$series->id.'/photos/from-url', 'https://cdn.example.com/a.png', $this->attacker)
            ->assertStatus(403);
        $this->postUrl('/api/series/999999/photos/from-url', 'https://cdn.example.com/a.png')
            ->assertStatus(404);
        $this->postUrl('/api/series/'.$series->id.'/photos/from-url', 'https://internal.example.com/a.png')
            ->assertStatus(422);

        $this->assertSame(0, $series->photos()->count());
        Http::assertNothingSent();
    }
}
