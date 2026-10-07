<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\Photo;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * On a phone the entity header used a fixed 2:1 box, which shrank the square
 * and portrait images most entities have to half the width with empty bands
 * at the sides; the profile photo was a small 160px square (#2158).
 */
class MobileHeaderImageTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_the_entity_header_image_keeps_its_own_shape_at_full_width(): void
    {
        $entity = Entity::factory()->create(['entity_status_id' => EntityStatus::ACTIVE]);
        $photo = Photo::factory()->create(['is_primary' => 1]);
        $entity->photos()->attach($photo->id);

        $html = $this->get(route('entities.show', $entity))->assertOk()->getContent();

        $this->assertStringNotContainsString('aspect-[2/1]', $html);
        $this->assertMatchesRegularExpression('/data-lightbox="entity-main".*?<img[^>]*class="[^"]*w-full h-auto max-h-\[70svh\] lg:max-h-\[600px\]/s', $html);
    }

    public function test_the_profile_photo_is_a_bigger_centred_square_on_phones(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        Profile::factory()->create(['user_id' => $user->id, 'setting_public_profile' => 1]);
        $photo = Photo::factory()->create(['is_primary' => 1]);
        $user->photos()->attach($photo->id);

        $this->actingAs($user)->get("/users/{$user->id}")->assertOk()
            ->assertSee('data-lightbox="user-photos" class="block w-64 h-64 max-w-full mx-auto sm:mx-0 sm:w-48 sm:h-48', false);
    }
}
