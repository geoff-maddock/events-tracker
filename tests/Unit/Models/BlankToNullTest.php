<?php

namespace Tests\Unit\Models;

use App\Models\Event;
use App\Models\Series;
use App\Models\Thread;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The BlankToNull trait (#2409): an empty form value on a listed date or id
 * is stored as null, anything else as a date or an integer.
 */
class BlankToNullTest extends TestCase
{
    /**
     * @return array<string, array{Event|Series|Thread, string}>
     */
    public static function dates(): array
    {
        $cases = [];
        foreach (['soundcheck_at', 'door_at', 'start_at', 'end_at', 'cancelled_at'] as $key) {
            $cases['event '.$key] = [new Event(), $key];
        }
        foreach (['founded_at', 'soundcheck_at', 'door_at', 'start_at', 'end_at', 'cancelled_at'] as $key) {
            $cases['series '.$key] = [new Series(), $key];
        }

        return $cases;
    }

    /**
     * @return array<string, array{Event|Series|Thread, string}>
     */
    public static function ints(): array
    {
        $cases = [];
        foreach (['promoter_id', 'series_id', 'venue_id'] as $key) {
            $cases['event '.$key] = [new Event(), $key];
        }
        foreach (['occurrence_week_id', 'occurrence_day_id', 'promoter_id', 'venue_id', 'length'] as $key) {
            $cases['series '.$key] = [new Series(), $key];
        }
        foreach (['event_id', 'thread_category_id'] as $key) {
            $cases['thread '.$key] = [new Thread(), $key];
        }

        return $cases;
    }

    #[Test]
    #[DataProvider('dates')]
    public function a_blank_date_is_null_and_anything_else_is_parsed(Event|Series|Thread $model, string $key): void
    {
        foreach (['', null, '0'] as $blank) {
            $model->setAttribute($key, '2026-03-04 19:00:00');
            $model->setAttribute($key, $blank);
            $this->assertNull($model->getAttribute($key));
        }

        // every way of writing an attribute goes through the same rule
        $model->fill([$key => '2026-03-04T19:00']);
        $this->assertInstanceOf(Carbon::class, $model->getAttribute($key));
        $this->assertSame('2026-03-04 19:00:00', $model->getAttribute($key)->format('Y-m-d H:i:s'));

        $model->forceFill([$key => Carbon::parse('2026-01-02 03:04:05')]);
        $this->assertSame('2026-01-02 03:04:05', $model->getAttribute($key)->format('Y-m-d H:i:s'));
    }

    #[Test]
    #[DataProvider('ints')]
    public function a_blank_or_zero_id_is_null_and_a_numeric_string_becomes_an_integer(Event|Series|Thread $model, string $key): void
    {
        foreach ([null, 0, '0'] as $blank) {
            $model->setAttribute($key, 7);
            $model->setAttribute($key, $blank);
            $this->assertNull($model->getAttribute($key));
        }

        $model->setAttribute($key, '12');
        $this->assertSame(12, $model->getAttribute($key));
    }

    #[Test]
    public function other_attributes_are_left_to_the_model(): void
    {
        $event = new Event();
        $event->setAttribute('name', '');
        $event->setAttribute('door_price', 0);

        $this->assertSame('', $event->name);
        // 0 is a real price, kept by the event's own mutator
        $this->assertSame(0, $event->getAttributes()['door_price']);
    }

    #[Test]
    public function a_subclass_keeps_the_rule(): void
    {
        $series = new class () extends Series {
        };
        $series->setAttribute('start_at', '');
        $series->setAttribute('venue_id', 0);

        $this->assertNull($series->start_at);
        $this->assertNull($series->venue_id);
    }
}
