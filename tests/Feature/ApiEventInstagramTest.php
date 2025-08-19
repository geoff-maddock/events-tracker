<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Photo;
use App\Models\User;
use App\Services\Integrations\Instagram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ApiEventInstagramTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_can_post_event_to_instagram()
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user, 'sanctum');

        $event = Event::factory()->create();

        // create a primary photo and attach it to the event so getPrimaryPhoto() succeeds
        $photo = Photo::factory()->create([
                'is_primary' => 1,
                'path' => 'test.jpg',
                'thumbnail' => 'test_thumb.jpg',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        $event->photos()->attach($photo->id);

        Storage::shouldReceive('disk')->with('external')->andReturnSelf()->byDefault();
        Storage::shouldReceive('url')->andReturn('http://example.com/test.jpg')->byDefault();

        $instagram = Mockery::mock(Instagram::class);
        $instagram->shouldReceive('getIgUserId')->andReturn(123);
        $instagram->shouldReceive('getPageAccessToken')->andReturn('token');
        $instagram->shouldReceive('uploadCarouselPhoto')->andReturn(111);
        $instagram->shouldReceive('checkStatus')->andReturn(true);
        $instagram->shouldReceive('createCarousel')->andReturn(999);
        $instagram->shouldReceive('publishMedia')->andReturn(555);

        $this->app->instance(Instagram::class, $instagram);

        $response = $this->postJson('/api/events/'.$event->id.'/instagram-post');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'id' => 555,
                 ]);
    }
}
