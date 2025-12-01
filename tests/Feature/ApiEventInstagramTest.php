<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Group;
use App\Models\Photo;
use App\Models\User;
use App\Models\Visibility;
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

        // Create a public event owned by the user
        $event = Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'created_by' => $user->id,
        ]);

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
        $instagram->shouldReceive('checkBatchStatus')->andReturn(true);
        $instagram->shouldReceive('createCarousel')->andReturn(999);
        $instagram->shouldReceive('checkStatus')->andReturn(true);
        $instagram->shouldReceive('publishMedia')->andReturn(555);

        $this->app->instance(Instagram::class, $instagram);

        $response = $this->postJson('/api/events/'.$event->id.'/instagram-post');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'id' => 555,
                 ]);
    }

    public function test_cannot_post_private_event_to_instagram()
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user, 'sanctum');

        // Create a private event owned by the user
        $event = Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PRIVATE,
            'created_by' => $user->id,
        ]);

        // Mock Instagram even though it shouldn't be called
        $instagram = Mockery::mock(Instagram::class);
        $this->app->instance(Instagram::class, $instagram);

        $response = $this->postJson('/api/events/'.$event->id.'/instagram-post');

        $response->assertStatus(401)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Unauthorized',
                 ]);
    }

    public function test_cannot_post_event_not_owned_without_admin()
    {
        $owner = User::factory()->create(['user_status_id' => 1]);
        $otherUser = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($otherUser, 'sanctum');

        // Create a public event owned by another user
        $event = Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'created_by' => $owner->id,
        ]);

        // Mock Instagram even though it shouldn't be called
        $instagram = Mockery::mock(Instagram::class);
        $this->app->instance(Instagram::class, $instagram);

        $response = $this->postJson('/api/events/'.$event->id.'/instagram-post');

        $response->assertStatus(401)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Unauthorized',
                 ]);
    }

    public function test_admin_can_post_any_public_event()
    {
        $owner = User::factory()->create(['user_status_id' => 1]);
        $admin = User::factory()->create(['user_status_id' => 1]);
        
        // Assign admin group to the user
        $adminGroup = Group::where('name', 'admin')->first();
        if (!$adminGroup) {
            $adminGroup = Group::factory()->create(['name' => 'admin']);
        }
        $admin->groups()->attach($adminGroup->id);
        
        $this->actingAs($admin, 'sanctum');

        // Create a public event owned by another user
        $event = Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'created_by' => $owner->id,
        ]);

        // create a primary photo and attach it to the event so getPrimaryPhoto() succeeds
        $photo = Photo::factory()->create([
                'is_primary' => 1,
                'path' => 'test.jpg',
                'thumbnail' => 'test_thumb.jpg',
                'created_by' => $owner->id,
                'updated_by' => $owner->id,
            ]);
        $event->photos()->attach($photo->id);

        Storage::shouldReceive('disk')->with('external')->andReturnSelf()->byDefault();
        Storage::shouldReceive('url')->andReturn('http://example.com/test.jpg')->byDefault();

        $instagram = Mockery::mock(Instagram::class);
        $instagram->shouldReceive('getIgUserId')->andReturn(123);
        $instagram->shouldReceive('getPageAccessToken')->andReturn('token');
        $instagram->shouldReceive('uploadCarouselPhoto')->andReturn(111);
        $instagram->shouldReceive('checkBatchStatus')->andReturn(true);
        $instagram->shouldReceive('createCarousel')->andReturn(999);
        $instagram->shouldReceive('checkStatus')->andReturn(true);
        $instagram->shouldReceive('publishMedia')->andReturn(555);

        $this->app->instance(Instagram::class, $instagram);

        $response = $this->postJson('/api/events/'.$event->id.'/instagram-post');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'id' => 555,
                 ]);
    }

    public function test_super_admin_can_post_any_public_event()
    {
        $owner = User::factory()->create(['user_status_id' => 1]);
        $superAdmin = User::factory()->create(['user_status_id' => 1]);
        
        // Assign super_admin group to the user
        $superAdminGroup = Group::where('name', 'super_admin')->first();
        if (!$superAdminGroup) {
            $superAdminGroup = Group::factory()->create(['name' => 'super_admin']);
        }
        $superAdmin->groups()->attach($superAdminGroup->id);
        
        $this->actingAs($superAdmin, 'sanctum');

        // Create a public event owned by another user
        $event = Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'created_by' => $owner->id,
        ]);

        // create a primary photo and attach it to the event so getPrimaryPhoto() succeeds
        $photo = Photo::factory()->create([
                'is_primary' => 1,
                'path' => 'test.jpg',
                'thumbnail' => 'test_thumb.jpg',
                'created_by' => $owner->id,
                'updated_by' => $owner->id,
            ]);
        $event->photos()->attach($photo->id);

        Storage::shouldReceive('disk')->with('external')->andReturnSelf()->byDefault();
        Storage::shouldReceive('url')->andReturn('http://example.com/test.jpg')->byDefault();

        $instagram = Mockery::mock(Instagram::class);
        $instagram->shouldReceive('getIgUserId')->andReturn(123);
        $instagram->shouldReceive('getPageAccessToken')->andReturn('token');
        $instagram->shouldReceive('uploadCarouselPhoto')->andReturn(111);
        $instagram->shouldReceive('checkBatchStatus')->andReturn(true);
        $instagram->shouldReceive('createCarousel')->andReturn(999);
        $instagram->shouldReceive('checkStatus')->andReturn(true);
        $instagram->shouldReceive('publishMedia')->andReturn(555);

        $this->app->instance(Instagram::class, $instagram);

        $response = $this->postJson('/api/events/'.$event->id.'/instagram-post');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'id' => 555,
                 ]);
    }

    public function test_event_share_is_created_when_posting_to_instagram()
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user, 'sanctum');

        // Create a public event owned by the user
        $event = Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'created_by' => $user->id,
        ]);

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
        $instagram->shouldReceive('checkBatchStatus')->andReturn(true);
        $instagram->shouldReceive('createCarousel')->andReturn(999);
        $instagram->shouldReceive('checkStatus')->andReturn(true);
        $instagram->shouldReceive('publishMedia')->andReturn(555);

        $this->app->instance(Instagram::class, $instagram);

        $response = $this->postJson('/api/events/'.$event->id.'/instagram-post');

        $response->assertStatus(200);

        // Verify that an EventShare record was created
        $this->assertDatabaseHas('event_shares', [
            'event_id' => $event->id,
            'platform' => 'instagram',
            'platform_id' => '555',
            'created_by' => $user->id,
        ]);
    }
}
