<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventToggleEssentialTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withExceptionHandling();
    }

    private function createAdmin(): User
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $user->assignGroup('admin');

        return $user;
    }

    public function test_admin_can_toggle_essential_flag_via_ajax(): void
    {
        $admin = $this->createAdmin();
        $event = Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'is_essential' => false,
        ]);

        $response = $this->actingAs($admin)->get(
            route('events.toggleEssential', ['id' => $event->id]),
            ['X-Requested-With' => 'XMLHttpRequest']
        );

        $response->assertOk();
        $response->assertJsonStructure(['Message', 'Success']);
        $this->assertTrue($event->fresh()->is_essential);

        // toggling again turns it back off
        $this->actingAs($admin)->get(
            route('events.toggleEssential', ['id' => $event->id]),
            ['X-Requested-With' => 'XMLHttpRequest']
        )->assertOk();

        $this->assertFalse($event->fresh()->is_essential);
    }

    public function test_non_admin_cannot_toggle_essential_flag(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $event = Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'is_essential' => false,
        ]);

        $this->actingAs($user)
            ->get(route('events.toggleEssential', ['id' => $event->id]))
            ->assertForbidden();

        $this->assertFalse($event->fresh()->is_essential);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $event = Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'is_essential' => false,
        ]);

        $this->get(route('events.toggleEssential', ['id' => $event->id]))
            ->assertRedirect(route('login'));

        $this->assertFalse($event->fresh()->is_essential);
    }
}
