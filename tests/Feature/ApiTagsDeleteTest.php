<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTagsDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /** @test */
    public function it_deletes_a_tag_via_api()
    {
        $user = User::factory()->create();
        $user->user_status_id = 1;
        $this->actingAs($user, 'sanctum');

        $tag = Tag::factory()->create();

        $response = $this->deleteJson("/api/tags/{$tag->slug}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }
}
