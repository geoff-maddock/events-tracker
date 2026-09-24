<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\ContentType;
use App\Models\Entity;
use App\Models\Event;
use App\Models\EventReview;
use App\Models\Forum;
use App\Models\Location;
use App\Models\ReviewType;
use App\Models\Series;
use App\Models\Thread;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * User-controlled text must not reach the page as markup: names, the flash
 * message, calendar links, map URLs, and bodies from untrusted authors.
 */
class StoredXssEscapingTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private const PAYLOAD = '<img src=x onerror=alert(1)>';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    private function makeUser(?string $group = null): User
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        if ($group) {
            $user->assignGroup($group);
        }

        return $user->fresh();
    }

    public function test_flash_message_is_json_encoded_into_the_script(): void
    {
        $response = $this->withSession(['flash_message' => [
            'title' => 'Success',
            'message' => 'You are now following the entity - "</script><script>alert(1)</script>',
            'level' => 'success',
        ]])->get('/');

        $response->assertOk();
        $this->assertStringNotContainsString('<script>alert(1)</script>', $response->getContent());
        // @json emits </script> as \u003C\/script\u003E, so the string cannot close the tag
        $this->assertStringContainsString('\\u003C\\/script\\u003E', $response->getContent());
    }

    public function test_event_page_escapes_venue_promoter_and_series_names(): void
    {
        $event = Event::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC, 'name' => 'Show "quoted" & more']);
        $event->venue->forceFill(['name' => 'Venue '.self::PAYLOAD])->saveQuietly();
        $event->promoter->forceFill(['name' => 'Promoter '.self::PAYLOAD])->saveQuietly();
        $series = Series::factory()->create(['name' => 'Series '.self::PAYLOAD]);
        $event->forceFill(['series_id' => $series->id])->saveQuietly();

        $response = $this->get(route('events.show', $event->fresh()));

        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringNotContainsString(self::PAYLOAD, $html);
        $this->assertStringContainsString('Venue &lt;img src=x onerror=alert(1)&gt;', $html);

        // the calendar link URL-encodes the name, so its quote cannot close the href
        $this->assertStringContainsString('text=Show%20%22quoted%22%20%26%20more', $event->fresh()->getGoogleCalendarLink());
    }

    public function test_only_http_map_urls_become_links(): void
    {
        $entity = Entity::factory()->create();
        $bad = Location::factory()->create(['entity_id' => $entity->id, 'map_url' => 'javascript:alert(1)', 'visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        $good = Location::factory()->create(['entity_id' => $entity->id, 'map_url' => 'https://maps.example.com/?q=1', 'visibility_id' => Visibility::VISIBILITY_PUBLIC]);

        $this->assertNull($bad->safeMapUrl());
        $this->assertSame('https://maps.example.com/?q=1', $good->safeMapUrl());

        $html = $this->get(route('entities.show', $entity))->assertOk()->getContent();
        $this->assertStringNotContainsString('href="javascript:alert(1)"', $html);
    }

    public function test_review_text_is_escaped_on_the_reviews_index(): void
    {
        $author = $this->makeUser();
        EventReview::create([
            'event_id' => Event::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC])->id,
            'user_id' => $author->id,
            'review_type_id' => ReviewType::query()->value('id'),
            'review' => 'Great show '.self::PAYLOAD,
            'attended' => 1,
            'confirmed' => 0,
        ]);

        $html = $this->actingAs($author)->get(route('reviews.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString(self::PAYLOAD, $html);
    }

    public function test_untrusted_blog_body_is_escaped_even_for_an_admin_viewer(): void
    {
        $author = $this->makeUser();
        $blog = Blog::factory()->create([
            'created_by' => $author->id,
            'body' => 'Hello '.self::PAYLOAD,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'content_type_id' => ContentType::HTML,
        ]);

        $html = $this->actingAs($this->makeUser('admin'))->get(route('blogs.show', $blog))->assertOk()->getContent();

        $this->assertStringNotContainsString(self::PAYLOAD, $html);
    }

    public function test_trusted_author_blog_html_still_renders(): void
    {
        $admin = $this->makeUser('admin');
        $blog = Blog::factory()->create([
            'created_by' => $admin->id,
            'body' => '<p class="zz-trusted">Welcome</p>',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        $this->get(route('blogs.show', $blog))->assertOk()->assertSee('<p class="zz-trusted">Welcome</p>', false);
    }

    public function test_untrusted_thread_body_is_escaped_on_the_thread_page(): void
    {
        $author = $this->makeUser();
        $thread = Thread::factory()->create([
            'forum_id' => Forum::factory()->create()->id,
            'body' => 'Thread '.self::PAYLOAD,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);
        $thread->forceFill(['created_by' => $author->id])->saveQuietly();

        // trust is decided by the author, so an admin viewer still gets escaped text
        $html = $this->actingAs($this->makeUser('admin'))->get(route('threads.show', $thread))->assertOk()->getContent();

        $this->assertStringNotContainsString(self::PAYLOAD, $html);
        $this->assertStringContainsString('Thread &lt;img src=x onerror=alert(1)&gt;', $html);
    }
}
