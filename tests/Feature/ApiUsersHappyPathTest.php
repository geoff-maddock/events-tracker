<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApiUsersHappyPathTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->actor = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->actingAs($this->actor, 'sanctum');
    }

    private User $actor;

    public function test_index_returns_user_collection(): void
    {
        $this->getJson('/api/users')->assertOk();
    }

    public function test_show_returns_user_resource(): void
    {
        $target = User::factory()->create(['name' => 'ZZ-Show-User']);

        $this->getJson('/api/users/'.$target->id)
            ->assertOk()
            ->assertJsonFragment(['name' => 'ZZ-Show-User']);
    }

    public function test_store_creates_user_and_profile(): void
    {
        $payload = [
            'name' => 'ZZ-NewAccount',
            'email' => 'zz-newaccount-'.uniqid().'@example.com',
            'password' => 'secret-pw-123',
        ];

        $response = $this->postJson('/api/users', $payload);

        $response->assertOk()->assertJsonFragment(['name' => 'ZZ-NewAccount']);

        $newUser = User::where('name', 'ZZ-NewAccount')->first();
        $this->assertNotNull($newUser);
        $this->assertNotNull($newUser->profile, 'Profile should be auto-created on user store.');
    }

    public function test_store_validates_invalid_payload(): void
    {
        $response = $this->postJson('/api/users', [
            'name' => 'x', // too short
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422);
    }

    public function test_update_modifies_user_and_profile_fields(): void
    {
        $target = User::factory()->create();
        $target->profile()->create([]);

        $response = $this->putJson('/api/users/'.$target->id, [
            'name' => 'ZZ-Updated-Name',
            'email' => $target->email,
            'profile' => [
                'first_name' => 'Updated',
                'last_name' => 'Surname',
            ],
        ]);

        $response->assertOk();

        $target->refresh();
        $this->assertSame('ZZ-Updated-Name', $target->name);
        $this->assertSame('Updated', $target->profile->first_name);
    }

    public function test_destroy_deletes_user(): void
    {
        $target = User::factory()->create();

        $response = $this->deleteJson('/api/users/'.$target->id);

        $response->assertStatus(204);
        $this->assertNull(User::find($target->id));
    }

    public function test_events_attending_returns_attended_events(): void
    {
        $event = Event::factory()->create();
        $responseTypeId = DB::table('response_types')->where('name', 'Attending')->value('id');

        DB::table('event_responses')->insert([
            'event_id' => $event->id,
            'user_id' => $this->actor->id,
            'response_type_id' => $responseTypeId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/users/'.$this->actor->id.'/events-attending')->assertOk();
    }

    public function test_show_with_invalid_id_returns_404(): void
    {
        $this->getJson('/api/users/9999999')->assertStatus(404);
    }
}
