<?php

namespace App\Services\Integrations\Discord;

use App\Models\DiscordTarget;
use App\Models\Event;
use App\Models\Tag;
use App\Services\EventTime;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Turns events into Discord webhook payloads (issue #2058).
 *
 * Two rules govern everything here:
 *
 *  1. Never emit a field with an empty value. Discord answers one with an
 *     opaque 400 that says nothing about which field was at fault, so an
 *     event missing a venue or a ticket link must omit those fields entirely
 *     rather than send a blank string.
 *  2. Clamp every string against config('discord.limits'). Over-long content
 *     is rejected the same opaque way.
 *
 * Every absolute instant comes from App\Services\EventTime, never from the
 * model's cast Carbon. Discord resolves <t:unix> tags against the viewer's own
 * clock, so an instant computed in the app's fixed-offset 'EST' zone shows an
 * hour late for the eight months of the year the timezone actually observes.
 *
 * Note this does not reuse Event::getBriefFormat() or getInstagramFormat():
 * both are shaped for their platform — hashtags, @handles, a 280-character
 * truncation — none of which belong in a Discord embed.
 */
class DiscordEmbedBuilder
{
    /**
     * Shorter summary used when a full-detail roundup overruns the description
     * budget. See digestDescription().
     */
    private const DIGEST_SUMMARY_FALLBACK = 60;

    /**
     * A single-event announcement, reminder, or manual post.
     *
     * @return array<string, mixed>
     */
    public function forEvent(Event $event, DiscordTarget $target): array
    {
        return $this->wrap($target, [$this->eventEmbed($event, $target)]);
    }

    /**
     * A weekly roundup. Rendered as one embed with a markdown list rather than
     * one embed per event: Discord caps a message at 10 embeds and 6000 total
     * characters, and a wall of flyers is unreadable in a busy channel.
     *
     * @param  Collection<int, Event>  $events
     * @return array<string, mixed>
     */
    public function forDigest(
        DiscordTarget $target,
        Collection $events,
        CarbonInterface $from,
        CarbonInterface $to,
    ): array {
        $embed = [
            'title' => $this->clamp(
                'Upcoming events · '.$from->format('M j').'–'.$to->format('M j'),
                'title'
            ),
            'description' => $this->digestDescription($events, (int) $this->limit('description')),
            'color' => $this->color($target),
            'footer' => ['text' => $this->clamp(config('app.app_name').' · '.rtrim((string) config('app.url'), '/'), 'footer')],
        ];

        return $this->wrap($target, [$embed]);
    }

    /**
     * A connection test, so an admin can confirm a webhook works before any
     * real event depends on it.
     *
     * @return array<string, mixed>
     */
    public function forTest(DiscordTarget $target): array
    {
        return $this->wrap($target, [[
            'title' => $this->clamp('Test message from '.config('app.app_name'), 'title'),
            'description' => $this->clamp(
                'If you can read this, the webhook for **'.$target->name.'** is working. '
                .'Events matching this channel\'s filters will appear here.',
                'description'
            ),
            'color' => $this->color($target),
        ]], includeMention: false);
    }

    /**
     * @return array<string, mixed>
     */
    private function eventEmbed(Event $event, DiscordTarget $target): array
    {
        $embed = [
            'title' => $this->clamp($event->name, 'title'),
            'url' => route('events.show', $event),
            'color' => $this->color($target),
            'fields' => $this->fields($event),
        ];

        // route() takes the model, not ->id: Event::getRouteKeyName() is 'slug',
        // so this yields the canonical /events/{slug} URL.

        if (null !== ($description = $this->description($event))) {
            $embed['description'] = $description;
        }

        if (null !== ($image = $event->getPrimaryPhotoPath())) {
            $embed['image'] = ['url' => $image];
        }

        if (null !== ($startsAt = EventTime::startsAt($event))) {
            $embed['timestamp'] = $startsAt->toIso8601String();
        }

        if (null !== $event->eventType) {
            $embed['author'] = ['name' => $this->clamp($event->eventType->name, 'title')];
        }

        if (null !== ($footer = $this->footer($event))) {
            $embed['footer'] = ['text' => $footer];
        }

        return $embed;
    }

    /**
     * Only fields with real content. Every entry here is conditional for the
     * reason in the class docblock.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fields(Event $event): array
    {
        $fields = [];

        if (null !== ($startsAt = EventTime::startsAt($event))) {
            // Discord renders <t:unix:F> in each viewer's own timezone, which
            // beats baking one timezone's string into the message.
            $when = '<t:'.$startsAt->timestamp.':F>';

            if (null !== ($doorsAt = EventTime::doorsAt($event))) {
                $when .= "\nDoors <t:".$doorsAt->timestamp.':t>';
            }

            $fields[] = $this->field('When', $when);
        }

        if (null !== $event->venue) {
            $fields[] = $this->field(
                'Where',
                '['.$event->venue->name.']('.route('entities.show', $event->venue).')',
                inline: true,
            );
        }

        if (null !== ($price = $this->price($event))) {
            $fields[] = $this->field('Price', $price, inline: true);
        }

        if ('' !== $event->age_format) {
            $fields[] = $this->field('Ages', $event->age_format, inline: true);
        }

        if (! empty($event->ticket_link)) {
            $fields[] = $this->field('Tickets', '['.Str::limit($event->ticket_link, 60).']('.$event->ticket_link.')');
        }

        return array_slice(array_filter($fields), 0, (int) $this->limit('fields'));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function field(string $name, ?string $value, bool $inline = false): ?array
    {
        $value = $this->clamp($value, 'field_value');

        if (null === $value) {
            return null;
        }

        return [
            'name' => $this->clamp($name, 'field_name'),
            'value' => $value,
            'inline' => $inline,
        ];
    }

    private function description(Event $event): ?string
    {
        $short = trim((string) $event->short);

        if ('' === $short) {
            $short = trim(Str::limit(strip_tags((string) $event->description), 300));
        }

        return $this->clamp($short, 'description');
    }

    /**
     * Null omits the field.
     *
     * There is deliberately no "Free" case. Event::setDoorPriceAttribute() and
     * setPresalePriceAttribute() both coerce a zero to null, so the schema
     * cannot distinguish a free show from one whose price nobody entered —
     * and announcing "Free" for an unpriced event would be worse than silence.
     */
    private function price(Event $event): ?string
    {
        $presale = $this->money($event->presale_price);
        $door = $this->money($event->door_price);

        if (null !== $presale && null !== $door) {
            return $presale.' presale / '.$door.' door';
        }

        return $presale ?? $door;
    }

    private function money(mixed $value): ?string
    {
        if (null === $value || ! is_numeric($value) || 0.0 === (float) $value) {
            return null;
        }

        return '$'.number_format((float) $value, 0);
    }

    private function footer(Event $event): ?string
    {
        $tags = $event->tags->pluck('name')->filter()->implode(' · ');

        return $this->clamp($tags, 'footer');
    }

    /**
     * The roundup body, at the richest detail level that fits.
     *
     * An entry with a summary and four linked tags costs roughly 450
     * characters, so a full week of events can overrun the 4096 available. A
     * roundup's job is to list the week: losing events off the end is worse
     * than losing a few words of summary, so an overflow shortens every entry
     * before it drops any.
     *
     * @param  Collection<int, Event>  $events
     */
    private function digestDescription(Collection $events, int $limit): string
    {
        $summary = (int) config('discord.digest.summary_length', 140);
        $tags = (int) config('discord.digest.tag_limit', 4);
        $entries = [];

        // [summary length, tag count], richest first; 0 drops that part of the
        // subtext. The last level is the bare headline the digest used before
        // the subtext existed, which comfortably fits max_events.
        $levels = [
            [$summary, $tags],
            [min(self::DIGEST_SUMMARY_FALLBACK, $summary), $tags],
            [0, $tags],
            [0, (int) min(2, $tags)],
            [0, 0],
        ];

        foreach ($levels as [$summaryLength, $tagLimit]) {
            $entries = $events->map(
                fn (Event $event): string => $this->digestEntry($event, $summaryLength, $tagLimit)
            )->all();

            $joined = implode("\n\n", $entries);

            if (mb_strlen($joined) <= $limit) {
                return $joined;
            }
        }

        // More events than fit even bare: drop the tail on a whole-entry
        // boundary, so nothing ends mid markdown-link.
        return $this->joinWithinLimit($entries, $limit);
    }

    /**
     * One digest entry: a headline, and — when the event has either — a second
     * line carrying its summary and up to $tagLimit linked tags.
     *
     * The second line is Discord subtext ('-# '), which renders small and grey.
     * That keeps a 25-event roundup scannable: the headlines still read as a
     * list, with the detail underneath rather than competing with them.
     */
    private function digestEntry(Event $event, int $summaryLength, int $tagLimit): string
    {
        $line = '**['.$this->escapeMarkdown($event->name).']('.route('events.show', $event).')**';

        if (null !== ($startsAt = EventTime::startsAt($event))) {
            $line .= ' — <t:'.$startsAt->timestamp.':D>';
        }

        if (null !== $event->venue) {
            $line .= ' · '.$this->escapeMarkdown($event->venue->name);
        }

        if (null !== ($price = $this->price($event))) {
            $line .= ' · '.$price;
        }

        $detail = array_merge(
            array_filter([$this->digestSummary($event, $summaryLength)]),
            $this->digestTags($event, $tagLimit),
        );

        if ([] !== $detail) {
            $line .= "\n-# ".implode(' · ', $detail);
        }

        return $line;
    }

    /**
     * The event's summary, flattened to a single line.
     *
     * Whitespace is collapsed because a newline would terminate the subtext
     * block, dropping the tags out of it and leaving a stray line in the
     * middle of the roundup.
     */
    private function digestSummary(Event $event, int $length): ?string
    {
        if ($length <= 0) {
            return null;
        }

        $summary = trim((string) $event->short);

        if ('' === $summary) {
            $summary = trim(strip_tags((string) $event->description));
        }

        $summary = trim((string) preg_replace('/\s+/u', ' ', $summary));

        if ('' === $summary) {
            return null;
        }

        return $this->escapeMarkdown(Str::limit($summary, $length, '…'));
    }

    /**
     * Tags as links to their own pages, capped — a busy event can carry a
     * dozen, which would swamp the summary it sits beside.
     *
     * @return array<int, string>
     */
    private function digestTags(Event $event, int $limit): array
    {
        if ($limit <= 0) {
            return [];
        }

        return $event->tags
            ->filter(fn (Tag $tag): bool => '' !== trim((string) $tag->name))
            ->take($limit)
            ->map(fn (Tag $tag): string => '['.$this->escapeMarkdown($tag->name).']('.route('tags.show', $tag).')')
            ->values()
            ->all();
    }

    /**
     * Assemble the message around its embeds.
     *
     * @param  array<int, array<string, mixed>>  $embeds
     * @return array<string, mixed>
     */
    private function wrap(DiscordTarget $target, array $embeds, bool $includeMention = true): array
    {
        $payload = ['embeds' => array_slice($embeds, 0, (int) $this->limit('embeds'))];

        if ($includeMention && ! empty($target->mention)) {
            $payload['content'] = $target->mention;
        }

        if (null !== ($username = $target->username ?? config('discord.username'))) {
            $payload['username'] = Str::limit((string) $username, 80, '');
        }

        if (null !== ($avatar = $target->avatar_url ?? config('discord.avatar_url'))) {
            $payload['avatar_url'] = (string) $avatar;
        }

        return $payload;
    }

    /**
     * Join entries while staying under a character budget, rather than
     * truncating mid-entry and leaving a broken markdown link. Entries are
     * separated by a blank line, since an entry is itself two lines.
     *
     * @param  array<int, string>  $entries
     */
    private function joinWithinLimit(array $entries, int $limit): string
    {
        $out = '';

        foreach ($entries as $line) {
            $candidate = ('' === $out) ? $line : $out."\n\n".$line;

            if (mb_strlen($candidate) > $limit) {
                break;
            }

            $out = $candidate;
        }

        return $out;
    }

    private function color(DiscordTarget $target): int
    {
        return (int) ($target->embed_color ?? config('discord.embed_color', 0x7B2FF7));
    }

    /**
     * Trim and cap a string, returning null when nothing is left — the caller
     * then omits the key entirely.
     */
    private function clamp(?string $value, string $limitKey): ?string
    {
        $value = trim((string) $value);

        if ('' === $value) {
            return null;
        }

        $limit = (int) $this->limit($limitKey);

        return mb_strlen($value) > $limit ? mb_substr($value, 0, $limit - 1).'…' : $value;
    }

    private function limit(string $key): int
    {
        return (int) config('discord.limits.'.$key, 1024);
    }

    /**
     * Keep an event name containing *, _, ~ or ` from reformatting the line.
     */
    private function escapeMarkdown(string $value): string
    {
        return preg_replace('/([*_~`|\\\\])/', '\\\\$1', $value) ?? $value;
    }
}
