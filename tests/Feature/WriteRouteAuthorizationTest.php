<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route as RouteDefinition;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Concerns\WriteRouteFixtures;
use Tests\TestCase;

/**
 * Authorization matrix for every write route (POST/PUT/PATCH/DELETE), #2186.
 *
 * Each route is classified by who may use it:
 *  - public: anyone, e.g. login, registration, and list filters that accept POST
 *  - user:   any signed-in user (create things, follow, like, attend…)
 *  - owner:  the record's owner, or an admin
 *  - admin:  admins only
 *
 * A new write route fails test_every_write_route_is_classified until it is
 * added here. Guests must be refused by every non-public route; a signed-in
 * stranger must be refused by every owner and admin route, and must not be
 * able to change or delete a record they don't own.
 */
class WriteRouteAuthorizationTest extends TestCase
{
    use RefreshDatabase;
    use WriteRouteFixtures;

    protected $seed = true;

    private const WRITE_ROUTES = [
        'public' => [
            'POST api/register',
            'POST api/tokens/create',
            'POST api/user/reset-password',
            'POST api/user/send-password-reset-email',
            'POST blogs/all',
            'POST blogs/filter',
            'POST broadcasting/auth',
            'POST categories/filter',
            'POST csp-report',
            // signed-URL unsubscribe and preferences (#2103): the signature is the credential
            'POST email/preferences/{id}',
            'POST email/resend',
            'POST email/unsubscribe/contact',
            'POST email/unsubscribe/{id}/{list}',
            'POST entities/filter',
            'POST entity-types/all',
            'POST entity-types/filter',
            'POST events/filter',
            'POST events/grid',
            'POST events/photos',
            'POST events/today',
            'POST forums/all',
            'POST forums/filter',
            'POST groups/all',
            'POST groups/filter',
            'POST login',
            'POST logout',
            'POST menus/all',
            'POST menus/filter',
            'POST password/confirm',
            'POST password/email',
            'POST password/reset',
            'POST permissions/all',
            'POST permissions/filter',
            'POST photos/filter',
            'POST posts/all',
            'POST posts/filter',
            'POST register',
            'POST reviews/filter',
            'POST roles/filter',
            'POST series/filter',
            'POST short-url',
            'POST threads/filter',
            'POST users/filter',
            'POST users/{id}/attending',
            'POST users/{id}/attending-ical',
            'POST users/{id}/interested-ical',
            'POST users/{id}/reset-user-attending',
            // SES bounces via SNS (#2103): authenticated by the SNS message signature
            'POST webhooks/ses',
        ],
        'user' => [
            'POST events/attending',
            'DELETE api/events/{event}/attend',
            'POST api/blogs',
            'POST api/blogs/filter',
            'POST api/entities',
            'POST api/entities/{entity}/follow',
            'POST api/entities/{entity}/unfollow',
            'POST api/entities/{id}/photos/from-url',
            'POST api/events',
            'POST api/events/{event}/attend',
            'POST api/events/{id}/photos/from-url',
            'POST api/forums/filter',
            'POST api/occurrence-days/filter',
            'POST api/occurrence-types/filter',
            'POST api/occurrence-weeks/filter',
            'POST api/posts',
            'POST api/posts/filter',
            'POST api/series',
            'POST api/series/{id}/photos/from-url',
            'POST api/series/{series}/follow',
            'POST api/series/{series}/unfollow',
            'POST api/tag-types/filter',
            'POST api/tags',
            'POST api/tags/{tag}/follow',
            'POST api/tags/{tag}/unfollow',
            'POST api/threads',
            'POST api/threads/filter',
            'POST api/users',
            'POST api/users/filter',
            'POST api/visibilities/filter',
            'POST blogs',
            'POST entities',
            'POST entities/following',
            'POST entities/quick-store',
            'POST entities/{entity}/claim',
            'POST entities/{entity}/comments',
            'POST entities/{id}/follow',
            'POST entities/{id}/unfollow',
            'POST events',
            'POST events/analyze-flyer',
            'POST events/{event}/comments',
            'POST events/{event}/reviews',
            'POST events/{id}/attend',
            'POST events/{id}/unattend',
            'POST feedback/opt-out',
            'POST images/analyze',
            'POST images/stash',
            'POST job-status/notifications/read',
            'POST onboarding/dismiss',
            'POST onboarding/follow',
            'POST posts',
            'POST posts/{id}/like',
            'POST posts/{id}/unlike',
            'POST series',
            'POST series/following',
            'POST series/{id}/follow',
            'POST series/{id}/unfollow',
            'POST tags',
            'POST tags/{id}/follow',
            'POST tags/{id}/unfollow',
            'POST threads',
            'POST threads/following',
            'POST threads/{id}/follow',
            'POST threads/{id}/like',
            'POST threads/{id}/unfollow',
            'POST threads/{id}/unlike',
            'POST threads/{thread}/posts',
            'POST users',
        ],
        'owner' => [
            'DELETE api/blogs/{blog}',
            'DELETE api/entities/{entity}',
            'DELETE api/entities/{id}/contacts/{contactId}',
            'DELETE api/entities/{id}/links/{linkId}',
            'DELETE api/entities/{id}/locations/{locationId}',
            'DELETE api/events/{event}',
            'DELETE api/links/{link}',
            'DELETE api/locations/{location}',
            'DELETE api/photos/{photo}',
            'DELETE api/posts/{post}',
            'DELETE api/series/{series}',
            'DELETE api/threads/{thread}',
            'DELETE api/users/{user}',
            'DELETE blogs/{blog}',
            'DELETE entities/{entity}',
            'DELETE entities/{entity}/comments/{comment}',
            'DELETE entities/{entity}/comments/{comment}/edit',
            'DELETE entities/{entity}/contacts/{contact}',
            'DELETE entities/{entity}/links/{link}',
            'DELETE entities/{entity}/locations/{location}',
            'DELETE events/{event}',
            'DELETE events/{event}/comments/{comment}',
            'DELETE events/{event}/reviews/{review}',
            'DELETE photos/{id}',
            'DELETE photos/{photo}',
            'DELETE posts/{post}',
            'DELETE series/{series}',
            'DELETE threads/{thread}',
            'DELETE users/{user}',
            'PATCH api/blogs/{blog}',
            'PATCH api/entities/{entity}',
            'PATCH api/entities/{id}/contacts/{contactId}',
            'PATCH api/entities/{id}/links/{linkId}',
            'PATCH api/entities/{id}/locations/{locationId}',
            'PATCH api/events/{event}',
            'PATCH api/locations/{location}',
            'PATCH api/posts/{post}',
            'PATCH api/series/{series}',
            'PATCH api/threads/{thread}',
            'POST api/entities/{id}/contacts',
            'POST api/entities/{id}/links',
            'POST api/entities/{id}/locations',
            'POST api/entities/{id}/photos',
            'POST api/events/{id}/instagram-post',
            'POST api/events/{id}/photos',
            'POST api/links',
            'POST api/locations',
            'POST api/photos/{photo}/set-primary',
            'POST api/photos/{photo}/unset-primary',
            'POST api/series/{id}/photos',
            'POST blogs/{id}/photos',
            'POST entities/{entity}/contacts',
            'POST entities/{entity}/contacts/{contact}/update',
            'POST entities/{entity}/links',
            'POST entities/{entity}/locations',
            'POST entities/{id}/instagram-post',
            'POST entities/{id}/instagram-story-post',
            'POST entities/{id}/photos',
            'POST entities/{id}/tweet',
            'POST entity-claims/{entityClaim}/withdraw',
            'POST events/{id}/discord-post',
            'POST events/{id}/instagram-post',
            'POST events/{id}/instagram-post-single',
            'POST events/{id}/instagram-story-post',
            'POST events/{id}/photos',
            'POST events/{id}/tweet',
            'POST feedback/{invitation}',
            'POST feedback/{invitation}/dismiss',
            'POST feedback/{invitation}/shown',
            'POST feedback/{invitation}/snooze',
            'POST photos/{id}/set-event',
            'POST photos/{id}/set-primary',
            'POST photos/{id}/unset-event',
            'POST photos/{id}/unset-primary',
            'POST series/{id}/photos',
            'POST threads/{id}/lock',
            'POST threads/{id}/unlock',
            'POST users/{id}/export-data',
            'POST users/{id}/photos',
            'PUT api/blogs/{blog}',
            'PUT api/entities/{entity}',
            'PUT api/entities/{id}/contacts/{contactId}',
            'PUT api/entities/{id}/links/{linkId}',
            'PUT api/entities/{id}/locations/{locationId}',
            'PUT api/events/{event}',
            'PUT api/links/{link}',
            'PUT api/locations/{location}',
            'PUT api/posts/{post}',
            'PUT api/series/{series}',
            'PUT api/threads/{thread}',
            'PUT api/users/{user}',
            'PUT blogs/{blog}',
            'PUT entities/{entity}',
            'PUT entities/{entity}/comments/{comment}',
            'PUT entities/{entity}/contacts/{contact}',
            'PUT entities/{entity}/links/{link}',
            'PUT entities/{entity}/locations/{location}',
            'PUT events/{event}',
            'PUT events/{event}/comments/{comment}',
            'PUT events/{event}/reviews/{review}',
            'PUT posts/{post}',
            'PUT series/{series}',
            'PUT tags/{tag}',
            'PUT threads/{thread}',
            'PUT users/{user}',
        ],
        'admin' => [
            'DELETE api/activities/{activity}',
            'DELETE api/entity-statuses/{entity_status}',
            'DELETE api/entity-types/{entity_type}',
            'DELETE api/event-statuses/{event_status}',
            'DELETE api/event-types/{event_type}',
            'DELETE api/forums/{forum}',
            'DELETE api/menus/{menu}',
            'DELETE api/roles/{role}',
            'DELETE api/tags/{tag}',
            'DELETE categories/{category}',
            'DELETE discord-targets/{discordTarget}',
            'DELETE entity-types/{entity_type}',
            'DELETE entity-types/{id}',
            'DELETE forums/{forum}',
            'DELETE groups/{group}',
            'DELETE menus/{menu}',
            'DELETE permissions/{permission}',
            'DELETE roles/{id}',
            'DELETE roles/{role}',
            'DELETE tags/{tag}',
            'PATCH api/entity-statuses/{entity_status}',
            'PATCH api/entity-types/{entity_type}',
            'PATCH api/event-statuses/{event_status}',
            'PATCH api/event-types/{event_type}',
            'PATCH api/forums/{forum}',
            'PATCH api/menus/{menu}',
            'PATCH api/roles/{role}',
            'PATCH feedback/responses/{surveyResponse}',
            'POST activity/filter',
            'POST api/activities/filter',
            'POST api/entity-statuses',
            'POST api/entity-types',
            'POST api/event-statuses',
            'POST api/event-types',
            'POST api/forums',
            'POST api/menus',
            'POST api/roles',
            'POST auto-relate-entity/{id}',
            'POST auto-relate-series/{id}',
            'POST categories',
            'POST discord-targets',
            'POST discord-targets/{discordTarget}/test',
            'POST discord-targets/{discordTarget}/toggle',
            'POST entity-claims/{entityClaim}/approve',
            'POST entity-claims/{entityClaim}/deny',
            'POST entity-types',
            'POST events/instagram-post-week',
            'POST events/instagram-todays-preview',
            'POST events/instagram-weekend-preview',
            'POST forums',
            'POST groups',
            'POST impersonate/{user}',
            'POST invite',
            'POST menus',
            'POST permissions',
            'POST purge',
            'POST roles',
            'POST tags/{tag}/instagram-post',
            'POST tags/{tag}/instagram-stories',
            'POST users/{id}/activate',
            'POST users/{id}/delete',
            'POST users/{id}/reminder',
            'POST users/{id}/reset-password',
            'POST users/{id}/restart-onboarding',
            'POST users/{id}/suspend',
            'POST users/{id}/weekly',
            'PUT api/entity-statuses/{entity_status}',
            'PUT api/entity-types/{entity_type}',
            'PUT api/event-statuses/{event_status}',
            'PUT api/event-types/{event_type}',
            'PUT api/forums/{forum}',
            'PUT api/menus/{menu}',
            'PUT api/roles/{role}',
            'PUT api/tags/{tag}',
            'PUT categories/{category}',
            'PUT discord-targets/{discordTarget}',
            'PUT entity-types/{entity_type}',
            'PUT forums/{forum}',
            'PUT groups/{group}',
            'PUT menus/{menu}',
            'PUT permissions/{permission}',
            'PUT roles/{role}',
        ],
    ];

    // Owner/admin PUT, PATCH and DELETE routes where the stranger check can't
    // show much, because the same payload has no effect even for the owner or
    // an admin (checked when this matrix was written, #2186). Every other such
    // route was confirmed to take effect for a privileged user, so "the
    // stranger changed nothing" there means the authorization check held.
    //  - PATCH feedback/responses/{surveyResponse}: survey responses have no text field for the marker
    //  - DELETE forums/{forum}: the fixture forum has a thread, and a forum with threads is not deleted (#2236)
    //  - DELETE api/forums/{forum}: same as the web route (409)

    /** The field a stranger's update would change, per bound model. */
    private const MARKER_FIELD = [
        'comment' => 'message', 'review' => 'review', 'link' => 'text', 'post' => 'body',
        'photo' => 'caption', 'activity' => 'object_name',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        // Instagram's client is built before the controller checks anything
        config(['app.facebook_system_user_access_token' => 'test', 'app.facebook_system_page_access_token' => 'test']);
        $this->makeWriteRouteFixtures();
    }

    /**
     * @return array<string, RouteDefinition>
     */
    private function writeRoutes(): array
    {
        $routes = [];
        foreach (Route::getRoutes()->getRoutes() as $route) {
            $methods = array_values(array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']));
            if (!$methods || str_starts_with($route->uri(), '_ignition')) {
                continue;
            }
            $routes[$methods[0].' '.$route->uri()] = $route;
        }

        return $routes;
    }

    private function classOf(string $key): ?string
    {
        foreach (self::WRITE_ROUTES as $class => $keys) {
            if (in_array($key, $keys, true)) {
                return $class;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function send(string $key, RouteDefinition $route, array $data = []): TestResponse
    {
        [$method] = explode(' ', $key, 2);
        $url = $this->urlFor($route);

        return str_starts_with($route->uri(), 'api/') ? $this->json($method, $url, $data) : $this->call($method, $url, $data);
    }

    private function refused(TestResponse $response): bool
    {
        if (in_array($response->status(), [401, 403, 404, 419], true)) {
            return true;
        }

        if ($response->isRedirect()) {
            $to = (string) $response->headers->get('Location');

            return str_ends_with($to, '/login')
                || str_contains($to, '/email/verify')
                || in_array(session('flash_message.level'), ['error', 'info'], true);
        }

        return false;
    }

    public function test_every_write_route_is_classified(): void
    {
        $routes = array_keys($this->writeRoutes());
        $classified = [];
        foreach (self::WRITE_ROUTES as $group) {
            $classified = [...$classified, ...$group];
        }

        $this->assertSame([], array_values(array_diff($routes, $classified)), 'Write routes missing from WRITE_ROUTES');
        $this->assertSame([], array_values(array_diff($classified, $routes)), 'WRITE_ROUTES lists routes that no longer exist');
        $this->assertSame(count($classified), count(array_unique($classified)), 'A route is listed twice');
    }

    public function test_guests_are_refused_by_every_non_public_write_route(): void
    {
        $failures = [];
        foreach ($this->writeRoutes() as $key => $route) {
            if ('public' === $this->classOf($key)) {
                continue;
            }
            $response = $this->send($key, $route);
            if (!$this->refused($response)) {
                $failures[] = "{$key} answered a guest with {$response->status()}";
            }
            session()->flush();
        }

        $this->assertSame([], $failures);
    }

    public function test_strangers_cannot_change_or_delete_records_they_do_not_own(): void
    {
        $stranger = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $failures = [];

        foreach ($this->writeRoutes() as $key => $route) {
            [$method] = explode(' ', $key, 2);
            if (!in_array($this->classOf($key), ['owner', 'admin'], true) || 'POST' === $method) {
                continue;
            }
            $name = $this->targetFixtureName($route);
            if (null === $name || !isset($this->fixtures[$name])) {
                continue;
            }
            $record = $this->fixtures[$name]->fresh();
            if (null === $record) {
                continue;
            }
            $field = self::MARKER_FIELD[$name] ?? 'name';
            // short (tag names max out at 16) and compared case-insensitively
            // (Role's name accessor ucfirst()s it)
            $marker = 'ZZH'.substr(md5($key), 0, 10);
            $payload = $this->updatePayload($record, $field, $marker);

            $this->actingAs($stranger, str_starts_with($route->uri(), 'api/') ? 'sanctum' : 'web');
            $this->send($key, $route, $payload);

            $after = $record->newQuery()->find($record->getKey());
            if ('DELETE' === $method && null === $after) {
                $failures[] = "{$key}: a stranger deleted the {$name}";
                $this->makeWriteRouteFixtures();
            } elseif ($after && 0 === strcasecmp((string) $after->getAttribute($field), $marker)) {
                $failures[] = "{$key}: a stranger changed the {$name}";
            }
            session()->flush();
        }

        $this->assertSame([], $failures);
    }

    public function test_strangers_are_refused_by_owner_and_admin_actions(): void
    {
        $stranger = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $failures = [];

        foreach ($this->writeRoutes() as $key => $route) {
            [$method] = explode(' ', $key, 2);
            if (!in_array($this->classOf($key), ['owner', 'admin'], true) || 'POST' !== $method) {
                continue;
            }
            $this->actingAs($stranger, str_starts_with($route->uri(), 'api/') ? 'sanctum' : 'web');
            // a valid body, so routes that validate before they authorize reach the check
            $response = $this->send($key, $route, $this->validPayload($key));
            if (!$this->refused($response)) {
                $failures[] = "{$key} answered a stranger with {$response->status()}";
            }
            session()->flush();
        }

        $this->assertSame([], $failures);
    }

    /**
     * A body that would update the record if the request were allowed: the
     * record's own attributes (nulls dropped, since some PATCH rules reject
     * them) with the marker field changed, so the payload passes validation
     * and only authorization stands in the way.
     *
     * @return array<string, mixed>
     */
    private function updatePayload(\Illuminate\Database\Eloquent\Model $record, string $field, string $marker): array
    {
        $payload = array_filter($record->attributesToArray(), fn ($value) => null !== $value);
        $payload[$field] = $marker;
        // a Discord target must match something or opt into every event
        $payload['match_all'] = 1;

        return $payload;
    }

    /**
     * The record a route acts on: its last bound parameter (entities/{entity}/links/{link} → link).
     */
    private function targetFixtureName(RouteDefinition $route): ?string
    {
        preg_match_all('#\{(\w+)\??\}#', $route->uri(), $m);
        $last = end($m[1]);
        if (false === $last) {
            return null;
        }

        return match ($last) {
            'linkId' => 'link',
            'locationId' => 'location',
            'contactId' => 'contact',
            'id', 'slug' => $this->prefixFixtureName($route->uri()),
            default => $last,
        };
    }

    /**
     * A body that passes validation for POST routes that validate before they authorize.
     *
     * @return array<string, mixed>
     */
    private function validPayload(string $key): array
    {
        Storage::fake('external');
        $entityId = $this->fixtures['entity']->getKey();
        $location = ['name' => 'ZZ Room', 'slug' => 'zz-room', 'city' => 'Pittsburgh', 'visibility_id' => 1, 'location_type_id' => $this->fixtures['location']->getAttribute('location_type_id')];

        return match (true) {
            str_ends_with($key, '/photos') => ['file' => UploadedFile::fake()->image('zz.jpg')],
            in_array($key, ['POST roles', 'POST api/roles'], true) => ['name' => 'ZZ Role', 'slug' => 'zz-role', 'short' => 'zz'],
            in_array($key, ['POST api/entity-statuses', 'POST api/event-types', 'POST api/event-statuses'], true) => ['name' => 'ZZ Name', 'slug' => 'zz-name'],
            str_ends_with($key, '/contacts/{contact}/update'), 'POST api/entities/{id}/contacts' === $key => ['name' => 'ZZ Contact', 'type' => 'Booking', 'visibility_id' => 1],
            'POST api/entities/{id}/links' === $key => ['text' => 'ZZ link', 'url' => 'https://example.com/zz'],
            'POST api/links' === $key => ['text' => 'ZZ link', 'url' => 'https://example.com/zz', 'entity_id' => $entityId],
            'POST api/entities/{id}/locations' === $key => $location,
            'POST api/locations' === $key => $location + ['entity_id' => $entityId],
            default => [],
        };
    }
}
