<?php

namespace Tests\Feature;

use App\Models\Follow;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagsIndexDisplayTypeTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_signed_in_user_can_filter_the_grid_to_followed_tags(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $followed = Tag::factory()->create(['name' => 'Zzfollowedtag']);
        Tag::factory()->create(['name' => 'Zzunfollowedtag']);
        Follow::create(['object_id' => $followed->id, 'object_type' => 'tag', 'user_id' => $user->id]);

        $html = $this->actingAs($user)
            ->get('/tags?search=Zz&filters[display_type]=following')
            ->assertOk()
            ->assertSee('Zzfollowedtag')
            ->assertDontSee('Zzunfollowedtag')
            ->assertSee('Tags Not Created')
            ->getContent();

        $this->assertMatchesRegularExpression('#<option value="following"\s+selected#', $html);
        // the sort/limit form carries the filter so changing the sort keeps it
        $this->assertStringContainsString('name="filters[display_type]" value="following"', $html);
    }

    public function test_guests_do_not_get_the_display_type_filter(): void
    {
        Tag::factory()->create(['name' => 'Zzguesttag']);

        $this->get('/tags?search=Zz&filters[display_type]=following')
            ->assertOk()
            ->assertSee('Zzguesttag')
            ->assertDontSee('filter_display_type', false);
    }
}
