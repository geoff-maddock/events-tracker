<?php

namespace App\Services\Player;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Photo;
use App\Models\Series;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * Describes the pop-out player page (#2366) for JSON-LD and link previews:
 * what the page is (the site's audio player) and what it's playing (the
 * queue source from its query string), with an image of that subject.
 */
class PlayerPageSchema
{
    public const DEFAULT_IMAGE = '/images/arcane-city-promo.jpg';

    public const PERSONAL_SOURCES = ['radar', 'attending'];

    /**
     * @param array<string, mixed> $query the player's query string
     *
     * @return array{title: string, description: string, image: string, url: string, indexable: bool, jsonLd: array<string, mixed>}
     */
    public function describe(array $query, ?User $user): array
    {
        $appName = (string) config('app.app_name');
        $source = is_string($query['source'] ?? null) ? $query['source'] : 'upcoming';
        $subject = $this->subject($source, $query, $user);

        $listening = $subject['label'] ?? $this->sourceLabel($source, $query);
        $title = $listening.' | '.$appName.' Player';
        $description = 'Listen to SoundCloud and Bandcamp tracks from '
            .($subject['from'] ?? lcfirst($listening))
            .' on the '.$appName.' audio player.';
        $image = $subject['image'] ?? url(self::DEFAULT_IMAGE);
        $url = route('player.show', $this->canonicalQuery($query));
        // a viewer's own radar or attending list is personal and empty for a crawler
        $indexable = !in_array($source, self::PERSONAL_SOURCES, true);

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $title,
            'url' => $url,
            'description' => $description,
            'image' => $image,
            'primaryImageOfPage' => ['@type' => 'ImageObject', 'url' => $image],
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => $appName,
                'url' => config('app.url'),
            ],
            'mainEntity' => [
                '@type' => 'WebApplication',
                'name' => $appName.' Player',
                'applicationCategory' => 'MultimediaApplication',
                'operatingSystem' => 'Any',
                'browserRequirements' => 'Requires JavaScript',
                'url' => route('player.show'),
            ],
        ];

        if ($subject) {
            $jsonLd['about'] = array_filter([
                '@type' => $subject['type'],
                'name' => $subject['name'],
                'url' => $subject['url'],
                'image' => $subject['image'],
            ]);
        }

        return compact('title', 'description', 'image', 'url', 'indexable', 'jsonLd');
    }

    /**
     * The one event, series, artist or venue the queue is built from, if it
     * exists and the viewer can see it, so a private name never leaks.
     *
     * @param array<string, mixed> $query
     *
     * @return array{type: string, name: string, label: string, from: string, url: string, image: ?string}|null
     */
    protected function subject(string $source, array $query, ?User $user): ?array
    {
        $id = is_numeric($query['id'] ?? null) ? (int) $query['id'] : null;
        $slug = is_string($query['slug'] ?? null) ? strtolower($query['slug']) : null;

        switch ($source) {
            case 'event':
                $event = $id ? Event::query()->visible($user)->whereKey($id)->first() : null;

                return $event ? [
                    'type' => 'Event',
                    'name' => (string) $event->name,
                    'label' => $event->name.' lineup',
                    'from' => 'the lineup of '.$event->name,
                    'url' => route('events.show', $event),
                    'image' => $event->getPrimaryPhotoPath(),
                ] : null;
            case 'series':
                $series = $id ? Series::query()->visible($user)->whereKey($id)->first() : null;

                return $series ? [
                    'type' => 'EventSeries',
                    'name' => (string) $series->name,
                    'label' => $series->name.' series',
                    'from' => 'the artists of the '.$series->name.' series',
                    'url' => route('series.show', $series),
                    'image' => $this->photoUrl($series->getPrimaryPhoto()),
                ] : null;
            case 'entity':
            case 'venue':
                $entity = $slug ? Entity::where('slug', $slug)->first() : null;

                return $entity ? [
                    'type' => $entity->getSchemaType(),
                    'name' => (string) $entity->name,
                    'label' => $source === 'venue' ? 'Shows at '.$entity->name : (string) $entity->name,
                    'from' => $source === 'venue' ? 'shows at '.$entity->name : (string) $entity->name,
                    'url' => route('entities.show', $entity->slug),
                    'image' => $entity->getPrimaryPhotoPath(),
                ] : null;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $query
     */
    protected function sourceLabel(string $source, array $query): string
    {
        $filters = is_array($query['filters'] ?? null) ? $query['filters'] : [];
        $tag = is_string($filters['tag'] ?? null) && $filters['tag'] !== '' ? $filters['tag'] : null;

        return match ($source) {
            'radar' => 'Your radar',
            'attending' => 'Shows you\'re attending',
            'entities' => $tag ? 'Artists tagged '.$tag : 'Artists',
            default => $tag ? 'Upcoming '.$tag.' shows' : 'Upcoming shows',
        };
    }

    /**
     * Just the parameters that pick the queue, so tracking junk isn't echoed back.
     *
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    protected function canonicalQuery(array $query): array
    {
        return array_filter(
            array_intersect_key($query, array_flip(['source', 'id', 'slug', 'filters', 'sort', 'direction'])),
            fn ($value) => $value !== null && $value !== '' && $value !== []
        );
    }

    protected function photoUrl(?Photo $photo): ?string
    {
        return $photo ? Storage::disk('external')->url($photo->getStoragePath()) : null;
    }
}
