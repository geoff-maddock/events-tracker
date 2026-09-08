<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class UserOptionsCacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /** @test */
    public function creating_a_user_forgets_the_cached_user_option_lists(): void
    {
        Cache::put(User::FORM_OPTIONS_CACHE_KEY, ['stale'], 3600);
        Cache::put(User::FILTER_OPTIONS_CACHE_KEY, ['stale'], 3600);

        User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->assertFalse(Cache::has(User::FORM_OPTIONS_CACHE_KEY));
        $this->assertFalse(Cache::has(User::FILTER_OPTIONS_CACHE_KEY));
    }

    /** @test */
    public function renaming_or_deleting_a_user_forgets_the_cached_user_option_lists(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        Cache::put(User::FORM_OPTIONS_CACHE_KEY, ['stale'], 3600);
        $user->update(['name' => 'Renamed User']);
        $this->assertFalse(Cache::has(User::FORM_OPTIONS_CACHE_KEY));

        Cache::put(User::FORM_OPTIONS_CACHE_KEY, ['stale'], 3600);
        $user->delete();
        $this->assertFalse(Cache::has(User::FORM_OPTIONS_CACHE_KEY));
    }

    /** @test */
    public function newly_registered_user_appears_in_event_owner_select_without_waiting_for_cache_expiry(): void
    {
        $this->withExceptionHandling();

        $admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $event = Event::factory()->create(['created_by' => $admin->id]);

        // Warm the owner-options cache by rendering the edit form once.
        $this->actingAs($admin)->get(route('events.edit', $event->slug))->assertStatus(200);
        $this->assertTrue(Cache::has(User::FORM_OPTIONS_CACHE_KEY));

        $newUser = User::factory()->create([
            'name' => 'Zed Freshly Registered',
            'user_status_id' => UserStatus::ACTIVE,
        ]);

        $response = $this->actingAs($admin)->get(route('events.edit', $event->slug));
        $response->assertStatus(200);
        $response->assertSee('Zed Freshly Registered');
        $response->assertSee('value="'.$newUser->id.'"', false);
    }
}
