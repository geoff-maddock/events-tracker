<?php

namespace Tests\Feature;

use Tests\TestCase;
use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SearchTest extends TestCase
{
    // refresh database and run migrations before test
    use RefreshDatabase;

    // reseed the database
    protected $seed = true;

    /** @test
     */
    public function a_search_returns_results()
    {
        $this->signIn();
        $faker = Faker::create();
        $keyword = $faker->domainWord;

        $response = $this->get('/search?keyword=' . $keyword);

        $response->assertStatus(200);
        $response->assertSee($keyword);
    }

    /** @test
     */
    public function search_orders_upcoming_events_ascending_and_past_events_descending()
    {
        $this->signIn();
        $keyword = 'zzyzxfest';

        // InnoDB FULLTEXT doesn't see rows created inside the test transaction
        // (see SearchVisibilityTest), so match events via the tag EXISTS branch.
        $tag = \App\Models\Tag::factory()->create(['name' => $keyword]);

        $make = function (string $name, \Carbon\Carbon $startAt) use ($tag) {
            \App\Models\Event::factory()->create([
                'name' => $name,
                'start_at' => $startAt,
                'end_at' => $startAt->copy()->addHour(),
                'visibility_id' => \App\Models\Visibility::VISIBILITY_PUBLIC,
            ])->tags()->attach($tag);
        };

        $make('Zzyzx far future', now()->addDays(30));
        $make('Zzyzx near future', now()->addDays(2));
        $make('Zzyzx recent past', now()->subDays(2));
        $make('Zzyzx older past', now()->subDays(30));

        \DB::enableQueryLog();
        $response = $this->get('/search?keyword=' . $keyword);

        $response->assertStatus(200);
        $response->assertSeeInOrder([
            'Zzyzx near future',
            'Zzyzx far future',
            'Zzyzx recent past',
            'Zzyzx older past',
        ]);

        // FULLTEXT relevance can't be exercised through the transaction, so
        // guard the sort priority at the SQL level: date order must come
        // before the MATCH relevance clause in ORDER BY.
        $sql = collect(\DB::getQueryLog())->pluck('query')->first(
            fn ($q) => str_contains($q, 'from `events`') && stripos($q, 'order by') !== false
        );
        $this->assertNotNull($sql);
        $orderBy = substr($sql, strripos($sql, 'order by'));
        $datePos = strpos($orderBy, 'TIMESTAMPDIFF');
        $relevancePos = strpos($orderBy, 'MATCH');
        $this->assertNotFalse($datePos);
        $this->assertNotFalse($relevancePos);
        $this->assertLessThan($relevancePos, $datePos);
    }

    /** @test
     * Search-page tag cards must render the real thumbnail image, not the
     * placeholder icon. Regression for #2037 (SearchService dropped
     * withGridThumbnail()).
     */
    public function search_tag_results_render_grid_thumbnails()
    {
        $this->signIn();
        $keyword = 'thumbtagfest';

        $tag = \App\Models\Tag::factory()->create(['name' => $keyword]);
        $event = \App\Models\Event::factory()->create([
            'name' => 'Unrelated Carrier Event',
            'start_at' => now()->addDays(3),
            'end_at' => now()->addDays(3)->addHour(),
            'visibility_id' => \App\Models\Visibility::VISIBILITY_PUBLIC,
        ]);
        $event->tags()->attach($tag);
        $photo = \App\Models\Photo::factory()->create([
            'is_primary' => 1,
            'thumbnail' => 'photos/thumbtagfest-thumb.jpg',
        ]);
        $event->photos()->attach($photo);

        $response = $this->get('/search?keyword=' . $keyword);

        $response->assertStatus(200);
        // alt is the tag name, which distinguishes the tag card's img from the
        // event card that also carries this photo.
        $response->assertSee('alt="' . $keyword . '"', false);
    }
}
