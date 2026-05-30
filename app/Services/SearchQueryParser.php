<?php

namespace App\Services;

use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\Tag;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

/**
 * Interprets a raw search query into structured intent so the search action can
 * shortcut straight to a filtered/detail page instead of the generic results.
 *
 * The parser is HTTP-agnostic: it returns structured data and never builds URLs
 * or redirects. Routing decisions live in the controller.
 *
 * Detection order:
 *   1. Date phrase ("this weekend", "Feb 16", "2026-02-16", "next month", …)
 *   2. Exact entity name/slug  (only when no date matched)
 *   3. Exact tag name/slug      (only when no date and no entity matched)
 *
 * When a date is present we deliberately skip entity/tag detection: a mixed
 * query like "techno this weekend" should render search results for the leftover
 * text with a date banner, not guess a redirect.
 */
class SearchQueryParser
{
    private const ENTITY_CACHE_KEY = 'search-parser-entity-slugs';

    private const TAG_CACHE_KEY = 'search-parser-tag-slugs';

    private const CACHE_TTL = 3600;

    /** @var array<string, int> month abbreviation => month number */
    private const MONTHS = [
        'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6,
        'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12,
    ];

    /**
     * Parse a raw keyword into structured intent.
     *
     * @return array{
     *   original: string,
     *   residualText: string,
     *   dateRange: array{start: string, end: string, label: string}|null,
     *   entity: array{slug: string, name: string}|null,
     *   tag: array{slug: string, name: string}|null,
     * }
     */
    public function parse(string $keyword): array
    {
        $original = trim(preg_replace('/\s+/', ' ', $keyword) ?? '');

        $result = [
            'original'     => $original,
            'residualText' => $original,
            'dateRange'    => null,
            'entity'       => null,
            'tag'          => null,
        ];

        if ($original === '') {
            return $result;
        }

        // 1. Date detection (strips the recognized fragment from the residual).
        $date = $this->detectDate($original);
        if ($date !== null) {
            $result['dateRange'] = $date['range'];
            $result['residualText'] = $date['residual'];

            return $result;
        }

        // 2/3. Entity then tag exact-match shortcuts (date-free queries only).
        if ($entity = $this->detectEntity($original)) {
            $result['entity'] = $entity;
            $result['residualText'] = '';

            return $result;
        }

        if ($tag = $this->detectTag($original)) {
            $result['tag'] = $tag;
            $result['residualText'] = '';
        }

        return $result;
    }

    /**
     * Detect a date phrase in the text. Returns the matched range plus the text
     * with the recognized fragment removed, or null when nothing matches.
     *
     * @return array{range: array{start: string, end: string, label: string}, residual: string}|null
     */
    private function detectDate(string $text): ?array
    {
        // Relative phrases, longest-first so "this weekend" wins over "this week".
        $relative = [
            'next weekend' => fn () => $this->weekendRange(Carbon::now()->addWeek()),
            'this weekend' => fn () => $this->weekendRange(Carbon::now()),
            'next week'    => fn () => [Carbon::now()->addWeek()->startOfWeek(), Carbon::now()->addWeek()->endOfWeek()],
            'this week'    => fn () => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
            'next month'   => fn () => [Carbon::now()->addMonthNoOverflow()->startOfMonth(), Carbon::now()->addMonthNoOverflow()->endOfMonth()],
            'this month'   => fn () => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            'tonight'      => fn () => [Carbon::now(), Carbon::now()],
            'today'        => fn () => [Carbon::now(), Carbon::now()],
            'tomorrow'     => fn () => [Carbon::now()->addDay(), Carbon::now()->addDay()],
        ];

        foreach ($relative as $phrase => $resolver) {
            if (preg_match('/\b' . preg_quote($phrase, '/') . '\b/i', $text)) {
                [$start, $end] = $resolver();

                return [
                    'range'    => $this->buildRange($start, $end, $phrase),
                    'residual' => $this->stripFragment($text, '/\b' . preg_quote($phrase, '/') . '\b/i'),
                ];
            }
        }

        // ISO date: 2026-02-16
        if (preg_match('/\b(\d{4}-\d{2}-\d{2})\b/', $text, $m)) {
            try {
                $day = Carbon::createFromFormat('Y-m-d', $m[1])->startOfDay();
            } catch (Throwable) {
                $day = null;
            }
            if ($day !== null) {
                return [
                    'range'    => $this->buildRange($day, $day, $day->format('M j, Y')),
                    'residual' => $this->stripFragment($text, '/\b' . preg_quote($m[1], '/') . '\b/'),
                ];
            }
        }

        // Month name + day (+ optional year): "Feb 16", "February 16th", "Feb 16, 2026"
        $monthPattern = '/\b(jan(?:uary)?|feb(?:ruary)?|mar(?:ch)?|apr(?:il)?|may|jun(?:e)?|jul(?:y)?'
            . '|aug(?:ust)?|sep(?:t(?:ember)?)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?)'
            . '\s+(\d{1,2})(?:st|nd|rd|th)?(?:,?\s+(\d{4}))?\b/i';
        if (preg_match($monthPattern, $text, $m)) {
            $month = self::MONTHS[strtolower(substr($m[1], 0, 3))];
            $dayNum = (int) $m[2];
            $year = isset($m[3]) && $m[3] !== '' ? (int) $m[3] : (int) Carbon::now()->format('Y');

            try {
                $day = Carbon::create($year, $month, $dayNum)->startOfDay();
                // Carbon::create silently overflows invalid days (e.g. Feb 30 -> Mar 2).
                $valid = $day !== null && (int) $day->format('n') === $month && (int) $day->format('j') === $dayNum;
            } catch (Throwable) {
                $day = null;
                $valid = false;
            }

            if ($valid && $day !== null) {
                return [
                    'range'    => $this->buildRange($day, $day, $day->format('M j, Y')),
                    'residual' => $this->stripFragment($text, $monthPattern),
                ];
            }
        }

        return null;
    }

    /**
     * "Weekend" relative to a reference day: the Saturday/Sunday of that week.
     * If the weekend has already passed this week, roll forward a week.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function weekendRange(Carbon $reference): array
    {
        $saturday = $reference->copy()->startOfWeek()->addDays(5);
        $sunday = $saturday->copy()->addDay();

        if ($sunday->endOfDay()->isPast()) {
            $saturday->addWeek();
            $sunday->addWeek();
        }

        return [$saturday, $sunday];
    }

    /**
     * Normalize a start/end pair into the filter-friendly range payload.
     *
     * @return array{start: string, end: string, label: string}
     */
    private function buildRange(Carbon $start, Carbon $end, string $label): array
    {
        return [
            'start' => $start->copy()->startOfDay()->format('Y-m-d H:i:s'),
            'end'   => $end->copy()->endOfDay()->format('Y-m-d H:i:s'),
            'label' => $label,
        ];
    }

    /**
     * Remove the first match of $pattern from $text and tidy whitespace.
     */
    private function stripFragment(string $text, string $pattern): string
    {
        $stripped = preg_replace($pattern, ' ', $text, 1) ?? $text;

        return trim(preg_replace('/\s+/', ' ', $stripped) ?? '');
    }

    /**
     * Exact entity match on the slugified query.
     *
     * @return array{slug: string, name: string}|null
     */
    private function detectEntity(string $text): ?array
    {
        $slug = Str::slug($text);
        if ($slug === '') {
            return null;
        }

        $map = $this->entitySlugMap();

        return isset($map[$slug]) ? ['slug' => $slug, 'name' => $map[$slug]] : null;
    }

    /**
     * Exact tag match on the slugified query.
     *
     * @return array{slug: string, name: string}|null
     */
    private function detectTag(string $text): ?array
    {
        $slug = Str::slug($text);
        if ($slug === '') {
            return null;
        }

        $map = $this->tagSlugMap();

        return isset($map[$slug]) ? ['slug' => $slug, 'name' => $map[$slug]] : null;
    }

    /**
     * Cached map of listed-entity slug => name for exact-match shortcuts.
     *
     * @return array<string, string>
     */
    private function entitySlugMap(): array
    {
        return Cache::remember(self::ENTITY_CACHE_KEY, self::CACHE_TTL, function () {
            return Entity::query()
                ->where('entity_status_id', '<>', EntityStatus::UNLISTED)
                ->whereNotNull('slug')
                ->pluck('name', 'slug')
                ->all();
        });
    }

    /**
     * Cached map of tag slug => name for exact-match shortcuts.
     *
     * @return array<string, string>
     */
    private function tagSlugMap(): array
    {
        return Cache::remember(self::TAG_CACHE_KEY, self::CACHE_TTL, function () {
            return Tag::query()
                ->whereNotNull('slug')
                ->pluck('name', 'slug')
                ->all();
        });
    }
}
