<?php

namespace App\Http\Controllers;

use App\Models\Action;
use App\Models\Activity;
use App\Models\Entity;
use App\Models\Event;
use App\Models\Follow;
use App\Models\Profile;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Post-signup "Getting To Know You" onboarding (issue #901).
 *
 * Presents popular entities, tags, and events to a freshly-verified user who is
 * not yet following anything, and turns their selections into follows.
 */
class OnboardingController extends Controller
{
    /**
     * Object types this onboarding flow can create follows for.
     */
    private const FOLLOWABLE_TYPES = ['entity', 'tag', 'event'];

    /**
     * Return the popular entities, tags, and events to seed the prompt with.
     */
    public function data(): JsonResponse
    {
        return response()->json([
            'entities' => $this->popularEntities(),
            'tags' => $this->popularTags(),
            'events' => $this->popularEvents(),
        ]);
    }

    /**
     * Create follows for the selected objects and mark onboarding complete.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'entities' => ['array'],
            'entities.*' => ['integer'],
            'tags' => ['array'],
            'tags.*' => ['integer'],
            'events' => ['array'],
            'events.*' => ['integer'],
        ]);

        $followed = 0;
        $followed += $this->followSelected($user, 'entity', Entity::class, $validated['entities'] ?? []);
        $followed += $this->followSelected($user, 'tag', Tag::class, $validated['tags'] ?? []);
        $followed += $this->followSelected($user, 'event', Event::class, $validated['events'] ?? []);

        $this->markOnboarding($user, 'onboarding_completed_at');

        return response()->json([
            'success' => true,
            'followed' => $followed,
        ]);
    }

    /**
     * Permanently dismiss the onboarding prompt for this user.
     */
    public function dismiss(Request $request): JsonResponse
    {
        $this->markOnboarding($request->user(), 'onboarding_dismissed_at');

        return response()->json(['success' => true]);
    }

    /**
     * Create idempotent follow rows for a set of selected object ids.
     *
     * @param array<int, int> $ids
     */
    private function followSelected(User $user, string $type, string $modelClass, array $ids): int
    {
        if (! in_array($type, self::FOLLOWABLE_TYPES, true) || empty($ids)) {
            return 0;
        }

        // Only follow objects that actually exist.
        $existingIds = $modelClass::query()->whereIn('id', $ids)->pluck('id');

        // Skip anything the user already follows so the action is idempotent.
        $alreadyFollowing = Follow::query()
            ->where('user_id', $user->id)
            ->where('object_type', $type)
            ->whereIn('object_id', $existingIds)
            ->pluck('object_id')
            ->all();

        $count = 0;

        foreach ($existingIds as $id) {
            if (in_array($id, $alreadyFollowing, true)) {
                continue;
            }

            $follow = new Follow();
            $follow->user_id = $user->id;
            $follow->object_type = $type;
            $follow->object_id = $id;
            $follow->save();

            $object = $modelClass::find($id);
            if ($object) {
                Activity::log($object, $user, Action::FOLLOW);
            }

            $count++;
        }

        return $count;
    }

    /**
     * Stamp the given onboarding column on the user's profile (creating the
     * profile row if the user does not have one yet).
     */
    private function markOnboarding(User $user, string $column): void
    {
        $profile = $user->profile;

        if (! $profile) {
            $profile = new Profile(['user_id' => $user->id]);
            $profile->user_id = $user->id;
        }

        $profile->{$column} = now();
        $profile->save();
    }

    /**
     * Popular entities = follows + upcoming events, highest first.
     *
     * @return array<int, array<string, mixed>>
     */
    private function popularEntities(): array
    {
        $entities = Entity::query()
            ->active()
            ->withCount(['follows', 'events'])
            ->with(['photos', 'entityType'])
            ->orderByDesc(DB::raw('follows_count + events_count'))
            ->orderBy('name')
            ->limit(8)
            ->get();

        return $entities->map(fn (Entity $entity) => [
            'id' => $entity->id,
            'name' => $entity->name,
            'subtitle' => $entity->entityType?->name,
            'image' => $entity->getPrimaryPhotoThumbnailPath(),
        ])->all();
    }

    /**
     * Popular tags = events + follows, highest first.
     *
     * @return array<int, array<string, mixed>>
     */
    private function popularTags(): array
    {
        $tags = Tag::query()
            ->withCount(['events', 'follows'])
            ->orderByDesc(DB::raw('events_count + follows_count'))
            ->orderBy('name')
            ->limit(12)
            ->get();

        return $tags->map(fn (Tag $tag) => [
            'id' => $tag->id,
            'name' => $tag->name,
        ])->all();
    }

    /**
     * Popular events = upcoming, public, most attendees first.
     *
     * @return array<int, array<string, mixed>>
     */
    private function popularEvents(): array
    {
        $events = Event::query()
            ->future()
            ->visible(auth()->user())
            ->withCount('attendees')
            ->with(['venue'])
            ->orderByDesc('attendees_count')
            ->orderBy('start_at')
            ->limit(6)
            ->get();

        return $events->map(fn (Event $event) => [
            'id' => $event->id,
            'name' => $event->name,
            'subtitle' => trim(collect([
                $event->start_at?->format('M j'),
                $event->venue?->name,
            ])->filter()->implode(' · ')) ?: null,
        ])->all();
    }
}
