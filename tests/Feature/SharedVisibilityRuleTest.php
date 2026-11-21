<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Series;
use App\Models\Thread;
use App\Models\User;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Events, series and threads share one visibility rule, the HasVisibility
 * trait (#2408): the list scope, the single-record check and the public-only
 * scope agree for each kind of viewer.
 */
class SharedVisibilityRuleTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private const KINDS = [
        'public' => Visibility::VISIBILITY_PUBLIC,
        'guarded' => Visibility::VISIBILITY_GUARDED,
        'private' => Visibility::VISIBILITY_PRIVATE,
        'proposal' => Visibility::VISIBILITY_PROPOSAL,
        'cancelled' => Visibility::VISIBILITY_CANCELLED,
    ];

    /**
     * @return array<string, array{0: class-string<Event|Series|Thread>, 1: bool}>
     */
    public static function models(): array
    {
        // the model, and whether its cancelled records still open for everyone
        return [
            'events' => [Event::class, true],
            'series' => [Series::class, true],
            'threads' => [Thread::class, false],
        ];
    }

    /**
     * One record of each visibility, all created by $owner.
     *
     * @param class-string<Event|Series|Thread> $model
     *
     * @return array<string, Event|Series|Thread>
     */
    private function oneOfEach(string $model, User $owner): array
    {
        $table = (new $model())->getTable();
        DB::table($table)->delete();

        $records = [];
        foreach (self::KINDS as $kind => $visibility) {
            $record = $model::factory()->create(['visibility_id' => $visibility]);
            // the attribution trait sets created_by from the signed-in user on create
            DB::table($table)->where('id', $record->id)->update(['created_by' => $owner->id]);
            $records[$kind] = $record->fresh();
        }

        return $records;
    }

    /**
     * @param class-string<Event|Series|Thread> $model
     */
    #[DataProvider('models')]
    public function test_lists_follow_one_rule(string $model, bool $opensWhenCancelled): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $records = $this->oneOfEach($model, $owner);
        $kindsFor = fn (?User $user) => collect($records)
            ->filter(fn ($record) => $model::visible($user)->whereKey($record->id)->exists())->keys()->all();

        $this->assertSame(['public'], $kindsFor(null));
        $this->assertSame(['public', 'guarded'], $kindsFor($member));
        $this->assertSame(['public', 'guarded', 'private', 'proposal'], $kindsFor($owner));

        $this->assertSame([$records['public']->id], $model::public()->pluck('id')->all());
    }

    /**
     * @param class-string<Event|Series|Thread> $model
     */
    #[DataProvider('models')]
    public function test_a_single_record_follows_the_same_rule(string $model, bool $opensWhenCancelled): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $admin = User::factory()->create();
        $admin->groups()->attach(\App\Models\Group::where('name', 'admin')->firstOrFail()->id);
        $records = $this->oneOfEach($model, $owner);
        $kindsFor = fn (?User $user) => collect($records)->filter(fn ($record) => $record->isVisibleTo($user))->keys()->all();

        $cancelled = $opensWhenCancelled ? ['cancelled'] : [];

        $this->assertSame(['public', ...$cancelled], $kindsFor(null));
        $this->assertSame(['public', 'guarded', ...$cancelled], $kindsFor($member));
        $this->assertSame(['public', 'guarded', 'private', 'proposal', ...$cancelled], $kindsFor($owner));
        $this->assertSame(array_keys(self::KINDS), $kindsFor($admin->fresh()));
    }

    public function test_the_rule_names_its_own_table_so_it_survives_a_join(): void
    {
        $member = User::factory()->create();

        // events and series both have visibility_id and created_by: an
        // unqualified column would be an "ambiguous column" SQL error here
        Series::visible($member)->join('events', 'events.series_id', '=', 'series.id')->count();
        Event::public()->join('series', 'events.series_id', '=', 'series.id')->count();
        Thread::visible($member)->join('events', 'events.id', '=', 'threads.event_id')->count();

        $this->assertStringContainsString('`threads`.`visibility_id`', Thread::visible($member)->toSql());
    }
}
