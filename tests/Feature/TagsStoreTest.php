<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagsStoreTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    /** @test */
    public function storing_a_new_tag_redirects_to_its_page()
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $response = $this->actingAs($user)->post('/tags', [
            'name' => 'Field Trip',
            'slug' => 'field-trip',
        ]);

        $tag = Tag::where('name', 'Field Trip')->firstOrFail();
        $response->assertRedirect(route('tags.show', ['tag' => $tag->slug]));
        $this->assertSame('field-trip', $tag->slug);
    }

    /** @test */
    public function storing_an_existing_tag_name_returns_to_the_form_with_input()
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        Tag::factory()->create(['name' => 'Field Trip', 'slug' => 'field-trip']);

        $response = $this->actingAs($user)
            ->from('/tags/create')
            ->post('/tags', [
                'name' => 'Field Trip',
                'slug' => 'field-trip-2',
            ]);

        $response->assertRedirect('/tags/create');
        $response->assertSessionHasInput('name', 'Field Trip');
        $this->assertSame(1, Tag::where('name', 'Field Trip')->count());
    }
}
