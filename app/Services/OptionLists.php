<?php

namespace App\Services;

use App\Models\ContentType;
use App\Models\Entity;
use App\Models\EventType;
use App\Models\Menu;
use App\Models\OccurrenceDay;
use App\Models\OccurrenceType;
use App\Models\OccurrenceWeek;
use App\Models\Scopes\UnlistedEntityScope;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use App\Models\Visibility;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

/**
 * The cached option lists behind the form selects and list filters (#2407):
 * name by id for forms, name by slug or by name for filters. This class owns
 * the cache keys, how long a list is kept and when it is dropped; nothing else
 * should name a `form-opts-…` or `filter-opts-…` key.
 *
 * Lists of things people add (tags, entities, users, series) are dropped when
 * one is saved or deleted, from the model's own events. The rest are small
 * lookup tables that only change with a deploy or an admin edit, and expire.
 */
class OptionLists
{
    private const HOUR = 3600;

    private const DAY = 86400;

    /** Cache key of each list, by what its options are keyed by. */
    private const KEYS = [
        'tags' => ['id' => 'form-opts-tags', 'slug' => 'filter-opts-tags-slug'],
        'venues' => ['id' => 'form-opts-venues', 'slug' => 'filter-opts-venues-slug', 'name' => 'filter-opts-venues-name'],
        'promoters' => ['id' => 'form-opts-promoters', 'slug' => 'filter-opts-promoters-slug'],
        'entities' => ['id' => 'form-opts-entities-all', 'slug' => 'filter-opts-entities-slug', 'name' => 'filter-opts-entities-name'],
        'activeEntities' => ['id' => 'form-opts-entities-active'],
        'eventTypes' => ['id' => 'form-opts-event-types', 'slug' => 'filter-opts-event-types-slug', 'name' => 'filter-opts-event-types-name'],
        'users' => ['id' => 'form-opts-users', 'name' => 'filter-opts-users-name'],
        'series' => ['id' => 'form-opts-series'],
        'publicSeries' => ['slug' => 'filter-opts-series-slug'],
    ];

    /** The lists that name entities; admins get their own copy of each. */
    private const ENTITY_LISTS = ['venues', 'promoters', 'entities', 'activeEntities'];

    /** @return array<int|string, string> */
    public static function tags(string $by = 'id'): array
    {
        return self::list('tags', $by, fn () => Tag::query());
    }

    /** @return array<int|string, string> */
    public static function venues(string $by = 'id'): array
    {
        return self::list('venues', $by, fn () => Entity::getVenues());
    }

    /** @return array<int|string, string> */
    public static function promoters(string $by = 'id'): array
    {
        return self::list('promoters', $by, fn () => Entity::whereHas('roles', fn ($q) => $q->where('name', '=', 'Promoter')));
    }

    /**
     * Every entity the viewer may see.
     *
     * @return array<int|string, string>
     */
    public static function entities(string $by = 'id'): array
    {
        return self::list('entities', $by, fn () => Entity::query());
    }

    /** @return array<int, string> */
    public static function activeEntities(): array
    {
        return self::list('activeEntities', 'id', fn () => Entity::active());
    }

    /** @return array<int|string, string> */
    public static function eventTypes(string $by = 'id'): array
    {
        return self::list('eventTypes', $by, fn () => EventType::query());
    }

    /** @return array<int|string, string> */
    public static function users(string $by = 'id'): array
    {
        return self::list('users', $by, fn () => User::query());
    }

    /**
     * Every series, for the selects of signed-in forms.
     *
     * @return array<int, string>
     */
    public static function series(): array
    {
        return self::list('series', 'id', fn () => Series::query());
    }

    /**
     * Public series by slug, for the filter panel everyone sees (#2361).
     *
     * @return array<string, string>
     */
    public static function publicSeries(): array
    {
        return self::list('publicSeries', 'slug', fn () => Series::public());
    }

    /** @return array<int, string> */
    public static function visibilities(): array
    {
        return Cache::remember('form-opts-visibilities', self::DAY, fn () => Visibility::orderBy('name', 'ASC')->pluck('name', 'id')->all());
    }

    /** @return array<int, string> */
    public static function menus(): array
    {
        return Cache::remember('form-opts-menus', self::HOUR, fn () => Menu::orderBy('name', 'ASC')->pluck('name', 'id')->all());
    }

    /** @return array<int, string> */
    public static function contentTypes(): array
    {
        return Cache::remember('form-opts-content-types', self::DAY, fn () => ContentType::orderBy('name', 'ASC')->pluck('name', 'id')->all());
    }

    /** @return array<int, string> */
    public static function occurrenceTypes(): array
    {
        return Cache::remember('form-opts-occurrence-types', self::DAY, fn () => OccurrenceType::pluck('name', 'id')->all());
    }

    /** @return array<int, string> */
    public static function occurrenceDays(): array
    {
        return Cache::remember('form-opts-occurrence-days', self::DAY, fn () => OccurrenceDay::pluck('name', 'id')->all());
    }

    /** @return array<int, string> */
    public static function occurrenceWeeks(): array
    {
        return Cache::remember('form-opts-occurrence-weeks', self::DAY, fn () => OccurrenceWeek::pluck('name', 'id')->all());
    }

    public static function forgetTags(): void
    {
        self::forget('tags');
    }

    /**
     * Drop every list that names entities, for both kinds of viewer.
     */
    public static function forgetEntities(): void
    {
        foreach (self::ENTITY_LISTS as $list) {
            self::forget($list);
        }
    }

    public static function forgetUsers(): void
    {
        self::forget('users');
    }

    /**
     * Drop the series lists and build the form list again straight away.
     */
    public static function refreshSeries(): void
    {
        self::forget('series');
        self::forget('publicSeries');
        self::series();
    }

    /**
     * The cache key of a list: for tests and for clearing one by hand.
     */
    public static function key(string $list, string $by = 'id'): string
    {
        $key = self::KEYS[$list][$by] ?? throw new InvalidArgumentException("No option list \"$list\" keyed by \"$by\".");

        // admins see unlisted entities, so their lists are kept apart
        return in_array($list, self::ENTITY_LISTS, true) && UnlistedEntityScope::viewerSeesUnlisted() ? $key.':admin' : $key;
    }

    /**
     * @param  Closure(): Builder<*> $query
     * @return array<int|string, string>
     */
    private static function list(string $list, string $by, Closure $query): array
    {
        return Cache::remember(self::key($list, $by), self::HOUR, fn () => $query()->reorder('name', 'ASC')->pluck('name', $by)->all());
    }

    private static function forget(string $list): void
    {
        foreach (self::KEYS[$list] as $key) {
            Cache::forget($key);
            Cache::forget($key.':admin');
        }
    }
}
