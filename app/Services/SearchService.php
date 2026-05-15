<?php

namespace App\Services;

use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\Event;
use App\Models\Series;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

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

        return [
            'events'        => $events,
            'eventsCount'   => $events->total(),
            'series'        => $series,
            'seriesCount'   => $series->total(),
            'entities'      => $entities,
            'entitiesCount' => $entities->total(),
            'tags'          => $tags,
            'tagsCount'     => $this->countLike(Tag::query(), ['name'], $keyword),
            'users'         => $users,
            'usersCount'    => $this->countLike(User::query(), ['name'], $keyword),
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

        if ($useFulltext) {
            $query->orderByRaw(
                'MATCH(events.name, events.short, events.description) AGAINST (?) DESC',
                [$keyword]
            );
        }
        $query->orderBy('start_at', 'DESC')->orderBy('name', 'ASC');

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

        if ($useFulltext) {
            $query->orderByRaw(
                'MATCH(series.name, series.short, series.description) AGAINST (?) DESC',
                [$keyword]
            );
        }
        $query->orderBy('start_at', 'DESC')->orderBy('name', 'ASC');

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
