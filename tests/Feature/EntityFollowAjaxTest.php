<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Follow;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AJAX callers of entity follow/unfollow (the audio player's star, #2366) get
 * a status and a `following` flag, never a redirect they'd mistake for success.
 */
class EntityFollowAjaxTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private const AJAX = ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_follow_and_unfollow_report_the_new_state(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $entity = Entity::factory()->create();

        $this->actingAs($user)->post('/entities/'.$entity->id.'/follow', [], self::AJAX)
            ->assertOk()->assertJsonPath('following', true);
        $this->assertNotNull($entity->followedBy($user));

        // already following: that's the state asked for, so it succeeds without a second row
        $this->actingAs($user)->post('/entities/'.$entity->id.'/follow', [], self::AJAX)
            ->assertOk()->assertJsonPath('following', true);
        $this->assertSame(1, Follow::where('user_id', $user->id)->where('object_id', $entity->id)->count());

        $this->actingAs($user)->post('/entities/'.$entity->id.'/unfollow', [], self::AJAX)
            ->assertOk()->assertJsonPath('following', false);
        $this->assertNull($entity->followedBy($user));
    }

    public function test_guests_and_missing_entities_get_error_statuses(): void
    {
        $entity = Entity::factory()->create();

        $this->post('/entities/'.$entity->id.'/follow', [], self::AJAX)->assertStatus(401);
        $this->post('/entities/'.$entity->id.'/unfollow', [], self::AJAX)->assertStatus(401);

        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->actingAs($user)->post('/entities/999999/follow', [], self::AJAX)->assertNotFound();
        $this->actingAs($user)->post('/entities/999999/unfollow', [], self::AJAX)->assertNotFound();
    }

    public function test_non_ajax_follow_still_redirects(): void
    {
        $entity = Entity::factory()->create();

        $this->post('/entities/'.$entity->id.'/follow')->assertRedirect();
    }
}
