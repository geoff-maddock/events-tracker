<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagsPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    protected function makeUser(?string $group = null): User
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        if ($group) {
            $user->assignGroup($group);
        }

        return $user;
    }

    protected function makeTagOwnedBy(User $owner): Tag
    {
        $tag = Tag::factory()->create();
        $tag->created_by = $owner->id;
        $tag->save();

        return $tag;
    }

    /** @test */
    public function admin_can_delete_a_tag_they_did_not_create()
    {
        $owner = $this->makeUser();
        $tag = $this->makeTagOwnedBy($owner);
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->delete("/tags/{$tag->slug}");

        $response->assertRedirect('/tags');
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    /** @test */
    public function super_admin_can_delete_a_tag_they_did_not_create()
    {
        $owner = $this->makeUser();
        $tag = $this->makeTagOwnedBy($owner);
        $superAdmin = $this->makeUser('super_admin');

        $response = $this->actingAs($superAdmin)->delete("/tags/{$tag->slug}");

        $response->assertRedirect('/tags');
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    /** @test */
    public function creator_cannot_delete_their_own_tag()
    {
        $owner = $this->makeUser();
        $tag = $this->makeTagOwnedBy($owner);

        $response = $this->actingAs($owner)->delete("/tags/{$tag->slug}");

        $response->assertForbidden();
        $this->assertDatabaseHas('tags', ['id' => $tag->id]);
    }

    /** @test */
    public function non_owner_cannot_delete_a_tag()
    {
        $owner = $this->makeUser();
        $tag = $this->makeTagOwnedBy($owner);
        $other = $this->makeUser();

        $response = $this->actingAs($other)->delete("/tags/{$tag->slug}");

        $response->assertForbidden();
        $this->assertDatabaseHas('tags', ['id' => $tag->id]);
    }

    /** @test */
    public function guest_cannot_delete_a_tag()
    {
        $owner = $this->makeUser();
        $tag = $this->makeTagOwnedBy($owner);

        $response = $this->delete("/tags/{$tag->slug}");

        $response->assertForbidden();
        $this->assertDatabaseHas('tags', ['id' => $tag->id]);
    }

    /** @test */
    public function non_owner_cannot_access_edit_page()
    {
        $owner = $this->makeUser();
        $tag = $this->makeTagOwnedBy($owner);
        $other = $this->makeUser();

        $response = $this->actingAs($other)->get("/tags/{$tag->slug}/edit");

        $response->assertForbidden();
    }

    /** @test */
    public function creator_can_access_edit_page_for_their_own_tag()
    {
        $owner = $this->makeUser();
        $tag = $this->makeTagOwnedBy($owner);

        $response = $this->actingAs($owner)->get("/tags/{$tag->slug}/edit");

        $response->assertOk();
    }

    /** @test */
    public function admin_can_access_edit_page_for_a_tag_they_did_not_create()
    {
        $owner = $this->makeUser();
        $tag = $this->makeTagOwnedBy($owner);
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->get("/tags/{$tag->slug}/edit");

        $response->assertOk();
    }

    /** @test */
    public function admin_sees_action_menu_on_tag_show_page()
    {
        $owner = $this->makeUser();
        $tag = $this->makeTagOwnedBy($owner);
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->get("/tags/{$tag->slug}");

        $response->assertOk();
        $response->assertSee('Delete Tag');
    }

    /** @test */
    public function creator_sees_edit_but_not_delete_on_tag_show_page()
    {
        $owner = $this->makeUser();
        $tag = $this->makeTagOwnedBy($owner);

        $response = $this->actingAs($owner)->get("/tags/{$tag->slug}");

        $response->assertOk();
        $response->assertSee('Edit Tag');
        $response->assertDontSee('Delete Tag');
    }

    /** @test */
    public function unrelated_user_does_not_see_action_menu_on_tag_show_page()
    {
        $owner = $this->makeUser();
        $tag = $this->makeTagOwnedBy($owner);
        $other = $this->makeUser();

        $response = $this->actingAs($other)->get("/tags/{$tag->slug}");

        $response->assertOk();
        $response->assertDontSee('Edit Tag');
        $response->assertDontSee('Delete Tag');
    }
}
