<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\SurveyQuestion.
 *
 * One question within a campaign. The `key` is the stable identifier used by
 * the JSON payload, by submitted answers and by the CSV export — never the
 * autoincrement id, which is not stable across environments.
 *
 * @property int                                                              $id
 * @property int                                                              $survey_campaign_id
 * @property string                                                           $key
 * @property int                                                              $sort
 * @property string                                                           $type
 * @property string                                                           $prompt
 * @property string|null                                                      $help
 * @property array<int, array{value: string, label: string}>|null             $options
 * @property int|null                                                         $min_value
 * @property int|null                                                         $max_value
 * @property bool                                                             $is_required
 * @property bool                                                             $is_active
 * @property string|null                                                      $depends_on_key
 * @property string|null                                                      $depends_on_value
 * @property \Illuminate\Support\Carbon|null                                  $created_at
 * @property \Illuminate\Support\Carbon|null                                  $updated_at
 * @property \App\Models\SurveyCampaign                                       $campaign
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\SurveyAnswer> $answers
 *
 * @method static Builder|SurveyQuestion newModelQuery()
 * @method static Builder|SurveyQuestion newQuery()
 * @method static Builder|SurveyQuestion query()
 *
 * @mixin \Eloquent
 */
class SurveyQuestion extends Eloquent
{
    use HasFactory;

    public const TYPE_RATING = 'rating';

    public const TYPE_SINGLE_CHOICE = 'single_choice';

    public const TYPE_MULTI_CHOICE = 'multi_choice';

    public const TYPE_TEXT = 'text';

    /**
     * Every question type the engine knows how to render, validate and store.
     *
     * @var array<int, string>
     */
    public const TYPES = [
        self::TYPE_RATING,
        self::TYPE_SINGLE_CHOICE,
        self::TYPE_MULTI_CHOICE,
        self::TYPE_TEXT,
    ];

    /**
     * Ceiling on stored free text, mirrored by the validation rules.
     */
    public const TEXT_MAX_LENGTH = 2000;

    protected $fillable = [
        'survey_campaign_id', 'key', 'sort', 'type', 'prompt', 'help',
        'options', 'min_value', 'max_value', 'is_required', 'is_active',
        'depends_on_key', 'depends_on_value',
    ];

    protected $casts = [
        'options' => 'array',
        'sort' => 'integer',
        'min_value' => 'integer',
        'max_value' => 'integer',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(SurveyCampaign::class, 'survey_campaign_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SurveyAnswer::class);
    }

    /**
     * True when this question only applies given a particular earlier answer.
     */
    public function isConditional(): bool
    {
        return $this->depends_on_key !== null && $this->depends_on_value !== null;
    }

    /**
     * Whether this question applies, given the answers collected so far.
     *
     * Unconditional questions always apply. A conditional one applies only when
     * its controlling question holds the expected value — which also means an
     * unanswered controller hides everything downstream of it.
     *
     * @param  array<string, mixed>  $answers  keyed by question key
     */
    public function appliesGiven(array $answers): bool
    {
        if (! $this->isConditional()) {
            return true;
        }

        $actual = $answers[$this->depends_on_key] ?? null;

        // Multi-choice controllers are unusual but cheap to support.
        if (is_array($actual)) {
            return in_array($this->depends_on_value, $actual, true);
        }

        return $actual !== null && (string) $actual === (string) $this->depends_on_value;
    }

    /**
     * True when the question stores its value in `value_option`.
     */
    public function isChoice(): bool
    {
        return in_array($this->type, [self::TYPE_SINGLE_CHOICE, self::TYPE_MULTI_CHOICE], true);
    }

    /**
     * The permitted `value` strings for a choice question.
     *
     * @return array<int, string>
     */
    public function optionValues(): array
    {
        $options = $this->options ?? [];

        return array_values(array_filter(array_map(
            static fn ($option) => is_array($option) && isset($option['value']) ? (string) $option['value'] : null,
            $options
        )));
    }

    /**
     * Look up an option's display label, falling back to the raw value so a
     * label removed from the seed data never blanks out historical answers.
     */
    public function labelForOption(string $value): string
    {
        foreach ($this->options ?? [] as $option) {
            if (is_array($option) && ($option['value'] ?? null) === $value) {
                return (string) ($option['label'] ?? $value);
            }
        }

        return $value;
    }
}
