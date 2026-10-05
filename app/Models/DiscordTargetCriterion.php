<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

/**
 * One include/exclude filter on a DiscordTarget (issue #2058).
 *
 * A single typed table rather than one pivot per criteria kind: every kind
 * points at a legacy int-keyed table, and adding a sixth kind should be a
 * validation-rule change rather than a migration. See DiscordTarget for the
 * AND/OR/veto semantics these combine under.
 *
 * @property int $id
 * @property int $discord_target_id
 * @property string $criteria_type
 * @property int $criteria_id
 * @property string $mode
 */
class DiscordTargetCriterion extends Eloquent
{
    use HasFactory;

    protected $table = 'discord_target_criteria';

    public const TYPE_TAG = 'tag';

    public const TYPE_ENTITY = 'entity';

    public const TYPE_EVENT_TYPE = 'event_type';

    public const TYPE_SERIES = 'series';

    public const TYPE_VENUE = 'venue';

    public const TYPE_PROMOTER = 'promoter';

    /** @var array<int, string> */
    public const TYPES = [
        self::TYPE_TAG,
        self::TYPE_ENTITY,
        self::TYPE_EVENT_TYPE,
        self::TYPE_SERIES,
        self::TYPE_VENUE,
        self::TYPE_PROMOTER,
    ];

    public const MODE_INCLUDE = 'include';

    public const MODE_EXCLUDE = 'exclude';

    /** @var array<int, string> */
    public const MODES = [
        self::MODE_INCLUDE,
        self::MODE_EXCLUDE,
    ];

    /**
     * The model each criteria_type resolves against, for labels and validation.
     *
     * @var array<string, class-string<Eloquent>>
     */
    public const TYPE_MODELS = [
        self::TYPE_TAG => Tag::class,
        self::TYPE_ENTITY => Entity::class,
        self::TYPE_EVENT_TYPE => EventType::class,
        self::TYPE_SERIES => Series::class,
        self::TYPE_VENUE => Entity::class,
        self::TYPE_PROMOTER => Entity::class,
    ];

    protected $fillable = [
        'discord_target_id',
        'criteria_type',
        'criteria_id',
        'mode',
    ];

    protected $casts = [
        'criteria_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<DiscordTarget, $this>
     */
    public function target(): BelongsTo
    {
        return $this->belongsTo(DiscordTarget::class, 'discord_target_id');
    }

    public function isExclude(): bool
    {
        return self::MODE_EXCLUDE === $this->mode;
    }

    /**
     * The referenced record's name, for the admin UI. Falls back to the raw
     * id when the target record has since been deleted.
     */
    public function label(): string
    {
        $class = self::TYPE_MODELS[$this->criteria_type] ?? null;

        if (null === $class) {
            return (string) $this->criteria_id;
        }

        $record = $class::query()->find($this->criteria_id);

        return $record->name ?? ('deleted #'.$this->criteria_id);
    }

    /**
     * Remove the criteria pointing at a tag, entity or series that is being
     * deleted (#2240), so no target keeps filtering on a record that's gone.
     *
     * Removing an exclude, or one of several includes of the same type, can
     * only narrow what was a dead reference. Removing the last include of its
     * type would widen the target (types are AND'd, so dropping a whole type
     * drops that constraint: "tag X at venue Y" would become "anything at
     * venue Y"), so such a target is disabled instead of left to post more
     * than it was set up for.
     */
    public static function forgetSubject(Eloquent $subject): void
    {
        $types = array_keys(array_filter(self::TYPE_MODELS, fn (string $class) => $subject instanceof $class));
        if ($types === []) {
            return;
        }

        $criteria = self::query()->whereIn('criteria_type', $types)->where('criteria_id', $subject->getKey())->with('target')->get();

        foreach ($criteria as $criterion) {
            $criterion->delete();

            $target = $criterion->target;
            if ($criterion->isExclude() || null === $target || !$target->is_enabled) {
                continue;
            }

            $stillScoped = $target->criteria()
                ->where('mode', self::MODE_INCLUDE)
                ->where('criteria_type', $criterion->criteria_type)
                ->exists();

            if (!$stillScoped) {
                $target->forceFill(['is_enabled' => false])->save();
                Log::warning(sprintf(
                    'Discord target %s (#%d) disabled: its only %s filter (#%d) was deleted.',
                    $target->name, $target->id, $criterion->criteria_type, $criterion->criteria_id
                ));
            }
        }
    }

    /**
     * Human-readable criteria type, e.g. 'event_type' => 'Event Type'.
     */
    public function typeLabel(): string
    {
        return ucwords(str_replace('_', ' ', $this->criteria_type));
    }
}
