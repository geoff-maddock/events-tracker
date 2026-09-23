<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\Photo;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Web photo writes: flag toggles need the photo's manager (PhotoPolicy::update),
 * the unused store/update actions are gone, and user/blog uploads need the owner.
 */
class PhotoWriteAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        Storage::fake('external');
    }

    private function makeUser(?string $group = null): User
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        if ($group) {
            $user->assignGroup($group);
        }

        return $user->fresh();
    }

    private function photoOwnedBy(User $user): Photo
    {
        return Photo::factory()->create([
            'name' => 'seeded.webp',
            'path' => 'photos/seeded.webp',
            'thumbnail' => 'photos/tn-seeded.webp',
            'is_primary' => 0,
            'is_event' => 0,
            'created_by' => $user->id,
        ]);
    }

    public function test_guest_cannot_toggle_photo_flags(): void
    {
        $photo = $this->photoOwnedBy($this->makeUser());

        foreach (['set-primary', 'set-event'] as $action) {
            $this->post('/photos/'.$photo->id.'/'.$action)->assertRedirect();
        }

        $photo->refresh();
        $this->assertSame(0, (int) $photo->is_primary);
        $this->assertSame(0, (int) $photo->is_event);
    }

    public function test_other_user_cannot_toggle_photo_flags(): void
    {
        $photo = $this->photoOwnedBy($this->makeUser());
        $photo->forceFill(['is_primary' => 1, 'is_event' => 1])->save();
        $other = $this->makeUser();

        foreach (['unset-primary', 'unset-event'] as $action) {
            $this->actingAs($other)->post('/photos/'.$photo->id.'/'.$action)->assertForbidden();
        }

        $photo->refresh();
        $this->assertSame(1, (int) $photo->is_primary);
        $this->assertSame(1, (int) $photo->is_event);
    }

    public function test_owner_can_toggle_photo_flags(): void
    {
        $owner = $this->makeUser();
        $photo = $this->photoOwnedBy($owner);

        $this->actingAs($owner)->post('/photos/'.$photo->id.'/set-primary')->assertRedirect();
        $this->actingAs($owner)->post('/photos/'.$photo->id.'/set-event')->assertRedirect();

        $photo->refresh();
        $this->assertSame(1, (int) $photo->is_primary);
        $this->assertSame(1, (int) $photo->is_event);
    }

    public function test_admin_can_toggle_photo_flags(): void
    {
        $photo = $this->photoOwnedBy($this->makeUser());

        $this->actingAs($this->makeUser('admin'))->post('/photos/'.$photo->id.'/set-primary')->assertRedirect();

        $this->assertSame(1, (int) $photo->fresh()->is_primary);
    }

    public function test_photo_store_and_update_routes_are_gone(): void
    {
        $owner = $this->makeUser();
        $photo = $this->photoOwnedBy($owner);

        $this->actingAs($owner)->post('/photos', ['name' => 'x', 'path' => 'photos/other.jpg'])->assertStatus(405);
        $this->actingAs($owner)->put('/photos/'.$photo->id, ['path' => 'photos/other.jpg'])->assertStatus(405);

        $this->assertSame('photos/seeded.webp', $photo->fresh()->path);
    }

    public function test_guest_and_other_user_cannot_add_a_photo_to_a_user(): void
    {
        $target = $this->makeUser();

        $this->post('/users/'.$target->id.'/photos', ['file' => UploadedFile::fake()->image('a.jpg')])->assertForbidden();
        $this->actingAs($this->makeUser())
            ->post('/users/'.$target->id.'/photos', ['file' => UploadedFile::fake()->image('a.jpg')])
            ->assertForbidden();

        $this->assertCount(0, $target->fresh()->photos);
    }

    public function test_user_can_add_a_photo_to_themselves(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->post('/users/'.$user->id.'/photos', ['file' => UploadedFile::fake()->image('a.jpg')])
            ->assertOk();

        $this->assertCount(1, $user->fresh()->photos);
    }

    public function test_guest_and_other_user_cannot_add_a_photo_to_a_blog(): void
    {
        $author = $this->makeUser();
        $blog = Blog::factory()->create(['created_by' => $author->id]);

        $this->post('/blogs/'.$blog->id.'/photos', ['file' => UploadedFile::fake()->image('a.jpg')])->assertUnauthorized();
        $this->actingAs($this->makeUser())
            ->post('/blogs/'.$blog->id.'/photos', ['file' => UploadedFile::fake()->image('a.jpg')])
            ->assertForbidden();

        $this->assertCount(0, $blog->fresh()->photos);
    }

    public function test_blog_author_can_add_a_photo(): void
    {
        $author = $this->makeUser();
        $blog = Blog::factory()->create(['created_by' => $author->id]);

        $this->actingAs($author)
            ->post('/blogs/'.$blog->id.'/photos', ['file' => UploadedFile::fake()->image('a.jpg')])
            ->assertOk();

        $this->assertCount(1, $blog->fresh()->photos);
    }
}
