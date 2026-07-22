<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Photo;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Mockery;
use Tests\TestCase;

/**
 * Photo attribution and management (issue #1989): uploads must set
 * created_by to the uploader — previously the column kept its DB default
 * of 1, so set-primary/unset-primary/destroy (which require strict creator
 * equality) failed for everyone except user 1.
 */
class ApiPhotoManagementTest extends TestCase
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
        $this->owner = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->attacker = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Replace Intervention's Image facade with a self-chained mock (same
     * pattern as ImageHandlerTest) so the upload pipeline runs without real
     * image processing against the fake external disk.
     */
    private function stubImageFacade(): void
    {
        $mock = Mockery::mock();
        $mock->shouldReceive('fit')->andReturnSelf();
        $mock->shouldReceive('encode')->andReturnSelf();
        $mock->shouldReceive('save')->andReturnSelf();
        $mock->shouldReceive('destroy')->andReturnNull();
        $mock->shouldReceive('basePath')->andReturnUsing(function () {
            return tempnam(sys_get_temp_dir(), 'photomgmt-');
        });

        Image::shouldReceive('make')->andReturn($mock);
    }

    private function seedPhotoOwnedBy(User $user): Photo
    {
        return Photo::factory()->create([
            'name' => 'seeded.webp',
            'path' => 'photos/seeded.webp',
            'thumbnail' => 'photos/tn-seeded.webp',
            'is_primary' => 0,
            'created_by' => $user->id,
            'updated_by' => null,
        ]);
    }

    /** @test */
    public function uploaded_photo_is_attributed_to_the_uploader(): void
    {
        $this->stubImageFacade();
        Mail::fake();

        $event = Event::factory()->create(['created_by' => $this->owner->id]);

        $this->actingAs($this->owner, 'sanctum');
        $this->post('/api/events/'.$event->id.'/photos',
            ['file' => UploadedFile::fake()->image('flyer.jpg')],
            ['Accept' => 'application/json'])
            ->assertStatus(201);

        $photo = $event->photos()->first();
        $this->assertNotNull($photo);
        $this->assertSame($this->owner->id, $photo->created_by,
            'Uploaded photo must be attributed to the uploader, not the DB default of 1.');
    }

    /** @test */
    public function uploader_can_manage_their_own_photo(): void
    {
        $photo = $this->seedPhotoOwnedBy($this->owner);

        $this->actingAs($this->owner, 'sanctum');

        $this->postJson('/api/photos/'.$photo->id.'/set-primary')
            ->assertStatus(200);
        $this->assertSame(1, $photo->fresh()->is_primary);

        $this->postJson('/api/photos/'.$photo->id.'/unset-primary')
            ->assertStatus(200);
        $this->assertSame(0, $photo->fresh()->is_primary);

        $this->deleteJson('/api/photos/'.$photo->id)
            ->assertStatus(204);
        $this->assertDatabaseMissing('photos', ['id' => $photo->id]);
    }

    /** @test */
    public function make_photo_falls_back_to_user_one_when_unauthenticated(): void
    {
        $this->stubImageFacade();

        // No actingAs: console/queued contexts keep the legacy attribution.
        $photo = (new \App\Services\ImageHandler())->makePhoto(
            UploadedFile::fake()->image('anon.png'));

        $this->assertSame(1, $photo->created_by);
    }

    /** @test */
    public function other_users_cannot_manage_anothers_photo(): void
    {
        $photo = $this->seedPhotoOwnedBy($this->owner);

        $this->actingAs($this->attacker, 'sanctum');

        $this->postJson('/api/photos/'.$photo->id.'/set-primary')
            ->assertStatus(403)
            ->assertJson(['status' => 'Permission Denied']);

        $this->postJson('/api/photos/'.$photo->id.'/unset-primary')
            ->assertStatus(403);

        $this->deleteJson('/api/photos/'.$photo->id)
            ->assertStatus(403);

        $this->assertDatabaseHas('photos', ['id' => $photo->id]);
        $this->assertSame(0, $photo->fresh()->is_primary);
    }
}
