<?php

namespace Tests\Feature\Concerns;

use App\Models\Activity;
use App\Models\Blog;
use App\Models\Comment;
use App\Models\Contact;
use App\Models\DiscordTarget;
use App\Models\Entity;
use App\Models\EntityClaim;
use App\Models\EntityStatus;
use App\Models\EntityType;
use App\Models\Event;
use App\Models\EventReview;
use App\Models\EventStatus;
use App\Models\EventType;
use App\Models\Forum;
use App\Models\Group;
use App\Models\Link;
use App\Models\Location;
use App\Models\Menu;
use App\Models\Permission;
use App\Models\Photo;
use App\Models\Post;
use App\Models\Role;
use App\Models\Series;
use App\Models\SurveyInvitation;
use App\Models\SurveyResponse;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\ThreadCategory;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;

/**
 * One record of every model a write route binds, all owned by $this->owner,
 * and the URL for a route with its parameters filled from them (#2186).
 */
trait WriteRouteFixtures
{
    protected User $owner;

    /** @var array<string, \Illuminate\Database\Eloquent\Model> */
    protected array $fixtures = [];

    private int $fixtureRound = 0;

    /**
     * Safe to call again (e.g. after a route deleted a fixture): every unique
     * name and slug carries this round's suffix.
     */
    protected function makeWriteRouteFixtures(): void
    {
        $n = ++$this->fixtureRound;
        $this->owner = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        // several models stamp created_by from the signed-in user
        $this->actingAs($this->owner);
        $owner = $this->owner->id;

        $entity = Entity::factory()->create(['created_by' => $owner, 'slug' => "zz-matrix-entity-{$n}"]);
        $event = Event::factory()->create(['created_by' => $owner, 'slug' => "zz-matrix-event-{$n}", 'visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        $series = Series::factory()->create(['created_by' => $owner, 'slug' => "zz-matrix-series-{$n}"]);
        $forum = Forum::factory()->create(['created_by' => $owner]);
        $thread = Thread::factory()->create(['forum_id' => $forum->id, 'visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        $post = Post::factory()->create(['thread_id' => $thread->id, 'created_by' => $owner]);
        $blog = Blog::factory()->create(['created_by' => $owner, 'slug' => "zz-matrix-blog-{$n}", 'content_type_id' => \App\Models\ContentType::PLAIN_TEXT]);
        $tag = Tag::factory()->create(['name' => "Zzmatrixtag{$n}", 'slug' => "zzmatrixtag{$n}", 'created_by' => $owner]);

        $link = Link::factory()->create(['url' => "https://example.com/zz-matrix-{$n}"]);
        $entity->links()->attach($link->id);
        $location = Location::factory()->create(['entity_id' => $entity->id, 'created_by' => $owner]);
        $contact = Contact::create(['name' => 'ZZ Matrix Contact', 'type' => 'Booking', 'visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        $entity->contacts()->attach($contact->id);
        $comment = $entity->comments()->create(['message' => 'A ZZ matrix comment']);
        $review = EventReview::create(['event_id' => $event->id, 'user_id' => $owner, 'review_type_id' => 1, 'review' => 'A ZZ matrix review', 'attended' => 1]);

        // PhotoFactory writes files; the matrix only needs a row
        $photoId = DB::table('photos')->insertGetId([
            'name' => 'zz-matrix.jpg', 'path' => 'photos/zz-matrix.jpg', 'thumbnail' => 'photos/tn-zz-matrix.jpg',
            'caption' => 'zz', 'created_by' => $owner, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $photo = Photo::findOrFail($photoId);
        $event->photos()->attach($photo->id);

        $claimTarget = Entity::factory()->create(['slug' => "zz-matrix-claimed-{$n}"]);
        $claim = EntityClaim::create(['entity_id' => $claimTarget->id, 'user_id' => $owner, 'status' => 'pending', 'message' => 'Mine ZZ']);

        $this->fixtures = [
            'entity' => $entity, 'event' => $event, 'series' => $series, 'forum' => $forum, 'thread' => $thread,
            'post' => $post, 'blog' => $blog, 'tag' => $tag, 'link' => $link, 'location' => $location,
            'contact' => $contact, 'comment' => $comment, 'review' => $review, 'photo' => $photo,
            'entityClaim' => $claim, 'user' => $this->owner,
            'menu' => Menu::factory()->create(),
            'role' => Role::first() ?? Role::factory()->create(),
            'entity_type' => EntityType::first(),
            'entity_status' => EntityStatus::first(),
            'event_type' => EventType::first(),
            'event_status' => EventStatus::first(),
            'category' => ThreadCategory::factory()->create(['forum_id' => $forum->id]),
            'group' => Group::firstOrCreate(['name' => "zzmatrix{$n}"], ['label' => 'ZZ Matrix', 'level' => 1, 'description' => '']),
            'permission' => Permission::firstOrCreate(['name' => "zz_matrix{$n}"], ['label' => 'ZZ Matrix', 'level' => 1, 'description' => '']),
            'discordTarget' => DiscordTarget::factory()->create(),
            'invitation' => SurveyInvitation::factory()->create(['user_id' => $owner]),
            'activity' => Activity::factory()->create(['user_id' => $owner]),
            'surveyResponse' => SurveyResponse::factory()->create(),
        ];

        // back to a guest, whichever guard was last used (sanctum's has no logout())
        auth()->guard('web')->logout();
        auth()->forgetGuards();
    }

    /**
     * The fixture an {id} or {slug} refers to, from the URI's first segment
     * (events/{id} → event).
     */
    protected function prefixFixtureName(string $uri): ?string
    {
        $first = explode('/', preg_replace('#^api/#', '', $uri))[0];

        return [
            'events' => 'event', 'entities' => 'entity', 'series' => 'series', 'photos' => 'photo',
            'posts' => 'post', 'threads' => 'thread', 'users' => 'user', 'blogs' => 'blog', 'tags' => 'tag',
            'roles' => 'role', 'entity-types' => 'entity_type', 'auto-relate-entity' => 'entity',
            'auto-relate-series' => 'series', 'forums' => 'forum', 'menus' => 'menu', 'locations' => 'location',
            'links' => 'link', 'comments' => 'comment', 'groups' => 'group', 'permissions' => 'permission',
        ][$first] ?? null;
    }

    /**
     * {id}, {slug}, {linkId}… are resolved from the URI's first segment
     * (events/{id} → the event), named parameters from the matching fixture.
     */
    protected function urlFor(Route $route): string
    {
        $uri = $route->uri();
        $prefixModel = $this->fixtures[$this->prefixFixtureName($uri) ?? ''] ?? null;

        return '/'.preg_replace_callback('#\{(\w+)\??\}#', function ($m) use ($prefixModel) {
            $name = $m[1];
            $model = match ($name) {
                'id', 'slug' => $prefixModel,
                'linkId' => $this->fixtures['link'],
                'locationId' => $this->fixtures['location'],
                'contactId' => $this->fixtures['contact'],
                default => $this->fixtures[$name] ?? null,
            };
            if (!$model) {
                return 'unbound-'.$name;
            }

            return (string) (in_array($name, ['id', 'linkId', 'locationId', 'contactId'], true) ? $model->getKey() : $model->getRouteKey());
        }, $uri);
    }
}
