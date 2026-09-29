<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\Link;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Entity link URLs must be http(s): scheme-less input is stored with
 * https://, other schemes are rejected on every write path, and a stored
 * value that isn't http(s) never renders as an href (#2220).
 */
class LinkUrlSchemesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private const BAD_URLS = ['javascript:alert(1)', 'JavaScript:void(0)', 'data:text/html,hi', 'ftp://example.com/x'];

    private User $owner;

    private Entity $entity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();

        $this->owner = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->entity = Entity::factory()->create([
            'slug' => 'zz-link-entity',
            'entity_status_id' => EntityStatus::where('name', 'Active')->value('id'),
        ]);
        $this->entity->owners()->attach($this->owner->id);
    }

    /**
     * A link stored without validation, as old or imported data could be.
     */
    private function storedLink(string $url, string $text = 'Stored link ZZ'): Link
    {
        $id = DB::table('links')->insertGetId([
            'url' => $url, 'text' => $text, 'title' => null, 'is_primary' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->entity->links()->attach($id);

        return Link::findOrFail($id);
    }

    public function test_web_link_store_rejects_other_schemes_and_normalizes_bare_hosts(): void
    {
        $this->actingAs($this->owner);

        foreach (self::BAD_URLS as $url) {
            $this->post("/entities/{$this->entity->slug}/links", ['text' => 'Bad link ZZ', 'url' => $url])
                ->assertSessionHasErrors('url');
        }
        $this->assertSame(0, Link::where('text', 'Bad link ZZ')->count());

        $this->post("/entities/{$this->entity->slug}/links", ['text' => 'Bare host ZZ', 'url' => ' www.example.com/band '])
            ->assertSessionHasNoErrors();
        $this->assertSame('https://www.example.com/band', Link::where('text', 'Bare host ZZ')->sole()->url);
    }

    public function test_web_link_update_rejects_other_schemes(): void
    {
        $link = Link::factory()->create(['url' => 'https://example.com/zz']);
        $this->entity->links()->attach($link->id);

        $this->actingAs($this->owner)
            ->put("/entities/{$this->entity->slug}/links/{$link->id}", ['text' => 'ZZ link', 'url' => 'javascript:alert(1)'])
            ->assertSessionHasErrors('url');

        $this->assertSame('https://example.com/zz', $link->fresh()->url);
    }

    public function test_api_link_endpoints_reject_other_schemes(): void
    {
        $link = Link::factory()->create(['url' => 'https://example.com/zz']);
        $this->entity->links()->attach($link->id);
        $this->actingAs($this->owner, 'sanctum');

        foreach (self::BAD_URLS as $url) {
            $this->postJson('/api/links', ['entity_id' => $this->entity->id, 'text' => 'Bad ZZ', 'url' => $url])
                ->assertStatus(422)->assertJsonValidationErrors('url');
            $this->postJson("/api/entities/{$this->entity->id}/links", ['text' => 'Bad ZZ', 'url' => $url])
                ->assertStatus(422)->assertJsonValidationErrors('url');
            $this->putJson("/api/entities/{$this->entity->id}/links/{$link->id}", ['text' => 'Bad ZZ', 'url' => $url])
                ->assertStatus(422)->assertJsonValidationErrors('url');
            $this->patchJson("/api/entities/{$this->entity->id}/links/{$link->id}", ['url' => $url])
                ->assertStatus(422)->assertJsonValidationErrors('url');
            $this->putJson("/api/links/{$link->id}", ['text' => 'Bad ZZ', 'url' => $url])
                ->assertStatus(422)->assertJsonValidationErrors('url');
        }

        $this->assertSame('https://example.com/zz', $link->fresh()->url);

        $this->postJson("/api/entities/{$this->entity->id}/links", ['text' => 'Bare ZZ', 'url' => 'shop.example.com'])
            ->assertStatus(201)
            ->assertJsonPath('url', 'https://shop.example.com');
    }

    public function test_a_stored_non_http_link_is_shown_as_text_not_an_href(): void
    {
        $this->storedLink('javascript:alert(1)', 'Unsafe ZZ');
        $this->storedLink('https://safe.example.com/zz', 'Safe ZZ');

        $html = $this->get("/entities/{$this->entity->slug}")->assertOk()->getContent();

        $this->assertStringNotContainsString('href="javascript:', $html);
        $this->assertStringContainsString('Unsafe ZZ', $html);
        $this->assertStringContainsString('href="https://safe.example.com/zz"', $html);
    }

    public function test_a_non_http_bandcamp_link_is_not_linked_from_the_entity_card(): void
    {
        $this->storedLink('javascript://bandcamp.com/%0Aalert(1)', 'Bandcamp ZZ');
        $tag = \App\Models\Tag::factory()->create(['name' => 'Zzlinktag', 'slug' => 'zzlinktag']);
        $this->entity->tags()->attach($tag->id);

        // the tag page renders the entity card, whose icon links to the Bandcamp link
        $html = $this->get('/tags/zzlinktag')->assertOk()->assertSee('zz-link-entity')->getContent();

        $this->assertStringNotContainsString('href="javascript:', $html);
    }

    public function test_json_ld_same_as_only_lists_http_urls(): void
    {
        $this->storedLink('javascript://bandcamp.com/%0Aalert(1)');
        $this->storedLink('https://artist.bandcamp.com');

        $sameAs = $this->entity->fresh()->getSameAsLinks();

        $this->assertContains('https://artist.bandcamp.com', $sameAs);
        foreach ($sameAs as $url) {
            $this->assertMatchesRegularExpression('#^https?://#', $url);
        }
    }

    public function test_the_backfill_migration_fixes_bare_hosts_and_typos_and_leaves_the_rest(): void
    {
        $bare = $this->storedLink('shop.example.com');
        $typo = $this->storedLink('https;//hyperfollow.com/ZZ');
        $text = $this->storedLink('pgh no wave collective');
        $bad = $this->storedLink('javascript:alert(1)');
        $good = $this->storedLink('http://already.example.com');

        $underscore = $this->storedLink('my_band.example.com/zz');

        ob_start();
        (require database_path('migrations/2026_09_29_000000_normalize_link_url_schemes.php'))->up();
        $output = (string) ob_get_clean();

        // the rows left alone are listed on the console for a hand fix
        $this->assertStringContainsString("#{$text->id}  pgh no wave collective", $output);
        $this->assertStringContainsString("#{$bad->id}  javascript:alert(1)", $output);
        $this->assertStringNotContainsString('shop.example.com', $output);
        $this->assertSame('https://my_band.example.com/zz', $underscore->fresh()->url);

        $this->assertSame('https://shop.example.com', $bare->fresh()->url);
        $this->assertSame('https://hyperfollow.com/ZZ', $typo->fresh()->url);
        $this->assertSame('pgh no wave collective', $text->fresh()->url);
        $this->assertSame('javascript:alert(1)', $bad->fresh()->url);
        $this->assertSame('http://already.example.com', $good->fresh()->url);
    }

    public function test_real_world_urls_that_are_not_strictly_rfc_valid_are_accepted_and_linked(): void
    {
        $urls = [
            'https://www.discogs.com/artist/123-Björk' => 'https://www.discogs.com/artist/123-Björk',
            'my_band.example.com' => 'https://my_band.example.com',
            'example.com:8080/zz' => 'https://example.com:8080/zz',
        ];
        $this->actingAs($this->owner);

        foreach ($urls as $input => $stored) {
            $this->post("/entities/{$this->entity->slug}/links", ['text' => 'Real ZZ '.$stored, 'url' => $input])
                ->assertSessionHasNoErrors();
            $this->assertSame($stored, Link::where('text', 'Real ZZ '.$stored)->sole()->url);
        }

        $html = $this->get("/entities/{$this->entity->slug}")->assertOk()->getContent();
        foreach ($urls as $stored) {
            $this->assertStringContainsString('href="'.e($stored).'"', $html);
        }
    }
}
