<?php

namespace App\Services;

use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\Event;
use App\Models\SearchLog;
use App\Models\Series;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SearchService
{
    /**
     * Minimum keyword length for MATCH...AGAINST. Below this we fall back to LIKE
     * because InnoDB's default ft_min_token_size is 3 (still wider than typical FULLTEXT
     * MyISAM defaults), and BOOLEAN MODE silently ignores tokens that are too short.
     */
    private const FULLTEXT_MIN_LENGTH = 3;

    /**
     * Run all six section searches and return an associative array of paginators.
     *
     * @return array{
     *   events: LengthAwarePaginator,
     *   eventsCount: int,
     *   series: LengthAwarePaginator,
     *   seriesCount: int,
     *   entities: LengthAwarePaginator,
     *   entitiesCount: int,
     *   tags: Paginator,
     *   tagsCount: int,
     *   users: Paginator,
     *   usersCount: int,
     *   threads: LengthAwarePaginator,
     *   threadsCount: int,
     * }
     */
    public function all(string $keyword, ?User $user, int $perPage = 20): array
    {
        $keyword = trim(preg_replace('/\s+/', ' ', $keyword) ?? '');
        $slug = Str::slug($keyword, '-');

        $events   = $this->events($keyword, $slug, $user, $perPage);
        $series   = $this->series($keyword, $slug, $user, $perPage);
        $entities = $this->entities($keyword, $perPage);
        $tags     = $this->tags($keyword, $perPage);
        $users    = $this->users($keyword, $perPage);
        $threads  = $this->threads($keyword, $perPage);

        $tagsCount    = $this->countLike(Tag::query(), ['name'], $keyword);
        $usersCount   = $this->countLike(User::query(), ['name'], $keyword);

        $this->logSearch($keyword, $user, 'web', $events->total() + $series->total() + $entities->total() + $tagsCount + $usersCount + $threads->total());

        return [
            'events'        => $events,
            'eventsCount'   => $events->total(),
            'series'        => $series,
            'seriesCount'   => $series->total(),
            'entities'      => $entities,
            'entitiesCount' => $entities->total(),
            'tags'          => $tags,
            'tagsCount'     => $tagsCount,
            'users'         => $users,
            'usersCount'    => $usersCount,
            'threads'       => $threads,
            'threadsCount'  => $threads->total(),
        ];
    }

    private function events(string $keyword, string $slug, ?User $user, int $perPage): LengthAwarePaginator
    {
        $useFulltext = $this->useFulltext($keyword);

        $query = Event::query()
            ->with('visibility', 'venue', 'tags', 'entities', 'series', 'eventType', 'threads')
            ->where(function (Builder $q) use ($keyword, $slug, $useFulltext) {
                // Text match on event's own columns.
                if ($useFulltext) {
                    $q->whereRaw(
                        'MATCH(events.name, events.short, events.description) AGAINST (? IN BOOLEAN MODE)',
                        [$this->booleanQuery($keyword)]
                    );
                } else {
                    $q->where('events.name', 'like', '%' . $keyword . '%');
                }

                // Related-entity / tag / series matches (case-insensitive).
                $q->orWhereHas('entities', fn ($r) => $r->where('slug', strtolower($slug)))
                    ->orWhereHas('tags', $this->ciNameMatch($keyword))
                    ->orWhereHas('series', $this->ciNameMatch($keyword))
                    ->orWhereHas('venue', $this->ciNameMatch($keyword))
                    ->orWhereHas('promoter', $this->ciNameMatch($keyword));
            })
            ->visible($user);

        $this->applyRecencyOrder($query, 'events');
        if ($useFulltext) {
            $query->orderByRaw(
                'MATCH(events.name, events.short, events.description) AGAINST (?) DESC',
                [$keyword]
            );
        }
        $query->orderByRaw('ABS(TIMESTAMPDIFF(SECOND, NOW(), events.start_at)) ASC')
            ->orderBy('events.name', 'ASC');

        return $query->paginate($perPage);
    }

    private function series(string $keyword, string $slug, ?User $user, int $perPage): LengthAwarePaginator
    {
        $useFulltext = $this->useFulltext($keyword);

        $query = Series::query()
            ->with('visibility', 'venue', 'tags', 'entities', 'eventType', 'threads', 'occurrenceType', 'occurrenceWeek', 'occurrenceDay')
            ->where(function (Builder $q) use ($keyword, $slug, $useFulltext) {
                if ($useFulltext) {
                    $q->whereRaw(
                        'MATCH(series.name, series.short, series.description) AGAINST (? IN BOOLEAN MODE)',
                        [$this->booleanQuery($keyword)]
                    );
                } else {
                    $q->where('series.name', 'like', '%' . $keyword . '%');
                }

                $q->orWhereHas('entities', fn ($r) => $r->where('slug', strtolower($slug)))
                    ->orWhereHas('tags', $this->ciNameMatch($keyword));
            })
            ->visible($user);

        $this->applyRecencyOrder($query, 'series');
        if ($useFulltext) {
            $query->orderByRaw(
                'MATCH(series.name, series.short, series.description) AGAINST (?) DESC',
                [$keyword]
            );
        }
        $query->orderByRaw('ABS(TIMESTAMPDIFF(SECOND, NOW(), series.start_at)) ASC')
            ->orderBy('series.name', 'ASC');

        return $query->paginate($perPage);
    }

    private function entities(string $keyword, int $perPage): LengthAwarePaginator
    {
        $useFulltext = $this->useFulltext($keyword);

        $query = Entity::query()
            ->with('tags', 'events', 'entityType', 'locations', 'entityStatus', 'user')
            ->where('entity_status_id', '<>', EntityStatus::UNLISTED)
            ->where(function (Builder $q) use ($keyword, $useFulltext) {
                if ($useFulltext) {
                    $q->whereRaw(
                        'MATCH(entities.name, entities.short, entities.description) AGAINST (? IN BOOLEAN MODE)',
                        [$this->booleanQuery($keyword)]
                    );
                } else {
                    $q->where('entities.name', 'like', '%' . $keyword . '%');
                }

                $q->orWhereHas('tags', $this->ciNameMatch($keyword))
                    ->orWhereHas('aliases', $this->ciNameMatch($keyword));
            });

        if ($useFulltext) {
            $query->orderByRaw(
                'MATCH(entities.name, entities.short, entities.description) AGAINST (?) DESC',
                [$keyword]
            );
        }
        $query->orderBy('entity_type_id', 'ASC')->orderBy('name', 'ASC');

        return $query->paginate($perPage);
    }

    private function tags(string $keyword, int $perPage): Paginator
    {
        return Tag::query()
            ->where('name', 'like', '%' . $keyword . '%')
            ->orderBy('name', 'ASC')
            ->simplePaginate($perPage);
    }

    private function users(string $keyword, int $perPage): Paginator
    {
        return User::query()
            ->where('name', 'like', '%' . $keyword . '%')
            ->orderBy('name', 'ASC')
            ->simplePaginate($perPage);
    }

    private function threads(string $keyword, int $perPage): LengthAwarePaginator
    {
        $useFulltext = $this->useFulltext($keyword);

        $query = Thread::query()
            ->with('visibility', 'entities', 'tags', 'posts', 'event', 'user')
            ->where(function (Builder $q) use ($keyword, $useFulltext) {
                if ($useFulltext) {
                    $q->whereRaw(
                        'MATCH(threads.name, threads.description, threads.body) AGAINST (? IN BOOLEAN MODE)',
                        [$this->booleanQuery($keyword)]
                    );
                } else {
                    $q->where('threads.name', 'like', '%' . $keyword . '%');
                }

                $q->orWhereHas('tags', $this->ciNameMatch($keyword));
            });

        if ($useFulltext) {
            $query->orderByRaw(
                'MATCH(threads.name, threads.description, threads.body) AGAINST (?) DESC',
                [$keyword]
            );
        }
        $query->orderBy('name', 'ASC');

        return $query->paginate($perPage);
    }

    /**
     * Lightweight grouped typeahead results: flat arrays per type.
     *
     * @param  array<int, string>|null  $types  Subset of ['events','entities','series','tags']. Null = all four.
     * @return array{
     *   query: string,
     *   results: array<string, list<array{id:int,name:string,slug:?string,subtitle:?string,url:string}>>,
     * }
     */
    public function lite(string $keyword, ?User $user, int $limit = 6, ?array $types = null): array
    {
        $keyword = trim(preg_replace('/\s+/', ' ', $keyword) ?? '');
        $types = $types ?: ['events', 'entities', 'series', 'tags'];
        $results = [];

        if ($keyword === '') {
            return ['query' => '', 'results' => array_fill_keys($types, [])];
        }

        $useFulltext = $this->useFulltext($keyword);

        if (in_array('events', $types, true)) {
            $q = Event::query()->select('id', 'name', 'slug', 'short', 'start_at')->visible($user);
            $this->applyTextMatch($q, 'events', ['name', 'short', 'description'], $keyword, $useFulltext);
            if ($useFulltext) {
                $q->orderByRaw('MATCH(events.name, events.short, events.description) AGAINST (?) DESC', [$keyword]);
            }
            $q->orderBy('start_at', 'DESC');
            $results['events'] = $q->limit($limit)->get()->map(fn ($e) => [
                'id'       => $e->id,
                'name'     => $e->name,
                'slug'     => $e->slug,
                'subtitle' => $e->start_at?->format('M j, Y'),
                'url'      => url('/events/' . $e->id),
            ])->all();
        }

        if (in_array('entities', $types, true)) {
            $q = Entity::query()->select('id', 'name', 'slug', 'short')
                ->where('entity_status_id', '<>', EntityStatus::UNLISTED);
            $this->applyTextMatch($q, 'entities', ['name', 'short', 'description'], $keyword, $useFulltext);
            if ($useFulltext) {
                $q->orderByRaw('MATCH(entities.name, entities.short, entities.description) AGAINST (?) DESC', [$keyword]);
            }
            $q->orderBy('name', 'ASC');
            $results['entities'] = $q->limit($limit)->get()->map(fn ($e) => [
                'id'       => $e->id,
                'name'     => $e->name,
                'slug'     => $e->slug,
                'subtitle' => $e->short,
                'url'      => url('/entities/' . $e->slug),
            ])->all();
        }

        if (in_array('series', $types, true)) {
            $q = Series::query()->select('id', 'name', 'slug', 'short')->visible($user);
            $this->applyTextMatch($q, 'series', ['name', 'short', 'description'], $keyword, $useFulltext);
            if ($useFulltext) {
                $q->orderByRaw('MATCH(series.name, series.short, series.description) AGAINST (?) DESC', [$keyword]);
            }
            $q->orderBy('name', 'ASC');
            $results['series'] = $q->limit($limit)->get()->map(fn ($s) => [
                'id'       => $s->id,
                'name'     => $s->name,
                'slug'     => $s->slug,
                'subtitle' => $s->short,
                'url'      => url('/series/' . $s->id),
            ])->all();
        }

        if (in_array('tags', $types, true)) {
            $tagResults = Tag::query()
                ->select('id', 'name', 'slug')
                ->where('name', 'like', '%' . $keyword . '%')
                ->orderByRaw('CASE WHEN LOWER(name) = ? THEN 0 ELSE 1 END', [mb_strtolower($keyword)])
                ->orderBy('name', 'ASC')
                ->limit($limit)
                ->get();
            $results['tags'] = $tagResults->map(fn ($t) => [
                'id'       => $t->id,
                'name'     => $t->name,
                'slug'     => $t->slug,
                'subtitle' => null,
                'url'      => url('/tags/' . $t->slug),
            ])->all();
        }

        $totalCount = array_sum(array_map('count', $results));
        $this->logSearch($keyword, $user, 'api', $totalCount);

        return ['query' => $keyword, 'results' => $results];
    }

    /**
     * Record a search to the search_logs table for later analytics.
     *
     * Skipped for empty queries (typeahead returns early before any query
     * is actually run). Failures are swallowed and logged to the default
     * channel — telemetry must never break a user-facing search.
     */
    private function logSearch(string $keyword, ?User $user, string $source, int $resultsCount): void
    {
        if ($keyword === '') {
            return;
        }
        try {
            $request = request();
            SearchLog::create([
                'user_id'       => $user?->id,
                'query'         => mb_substr($keyword, 0, 191),
                'results_count' => $resultsCount,
                'source'        => $source,
                'ip_address'    => $request?->ip(),
                'user_agent'    => $request ? mb_substr((string) $request->userAgent(), 0, 512) : null,
            ]);
        } catch (Throwable $e) {
            Log::warning('search log write failed', ['err' => $e->getMessage()]);
        }
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  array<int, string>  $columns
     */
    private function applyTextMatch(Builder $query, string $table, array $columns, string $keyword, bool $useFulltext): void
    {
        if ($useFulltext) {
            $cols = implode(',', array_map(fn ($c) => "$table.$c", $columns));
            $query->whereRaw(
                "MATCH($cols) AGAINST (? IN BOOLEAN MODE)",
                [$this->booleanQuery($keyword)]
            );
        } else {
            $query->where("$table.name", 'like', '%' . $keyword . '%');
        }
    }

    /**
     * Order results into three time buckets so upcoming events always come
     * before recent-past, which come before older — keeping discovery of
     * upcoming events as the primary use case for the site.
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    private function applyRecencyOrder(Builder $query, string $table): void
    {
        // start_at NULL is treated as "older" so undated rows don't outrank
        // real upcoming events.
        $query->orderByRaw(
            "CASE
                WHEN {$table}.start_at IS NULL                      THEN 2
                WHEN {$table}.start_at >= NOW()                     THEN 0
                WHEN {$table}.start_at >= NOW() - INTERVAL 60 DAY   THEN 1
                ELSE                                                     2
            END ASC"
        );
    }

    /**
     * Case-insensitive name comparison closure for whereHas relationships.
     */
    private function ciNameMatch(string $keyword): \Closure
    {
        return function ($q) use ($keyword) {
            $q->whereRaw('LOWER(name) = ?', [mb_strtolower($keyword)]);
        };
    }

    private function useFulltext(string $keyword): bool
    {
        // Strip non-alphanumeric to estimate token length the FT parser will keep.
        $stripped = preg_replace('/[^\p{L}\p{N}]+/u', '', $keyword) ?? '';
        return mb_strlen($stripped) >= self::FULLTEXT_MIN_LENGTH;
    }

    /**
     * Build a BOOLEAN MODE query string. Each safe token becomes `+token*` so all
     * terms are required and prefix-expanded. Operators in the user input are stripped.
     */
    private function booleanQuery(string $keyword): string
    {
        $cleaned = preg_replace('/[+\-><()~*"@]+/', ' ', $keyword) ?? '';
        $tokens = preg_split('/\s+/', trim($cleaned)) ?: [];
        $parts = [];
        foreach ($tokens as $token) {
            if ($token === '' || mb_strlen($token) < self::FULLTEXT_MIN_LENGTH) {
                continue;
            }
            $parts[] = '+' . $token . '*';
        }
        return $parts === [] ? $keyword : implode(' ', $parts);
    }

    /**
     * Total for simplePaginate'd sections so the search page can show counts.
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $base
     * @param  array<int, string>  $columns
     */
    private function countLike(Builder $base, array $columns, string $keyword): int
    {
        $base->where(function (Builder $q) use ($columns, $keyword) {
            foreach ($columns as $col) {
                $q->orWhere($col, 'like', '%' . $keyword . '%');
            }
        });
        return $base->count();
    }
}
