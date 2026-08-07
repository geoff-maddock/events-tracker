<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\SurveyAnswer.
 *
 * One value within a response. Which column is populated depends on the
 * question type: rating -> value_int, single/multi choice -> value_option
 * (one row per selected option), text -> value_text.
 *
 * Typed columns rather than a single JSON blob because the admin summary is
 * AVG(value_int) per question and GROUP BY value_option, both of which want
 * plain indexed SQL.
 *
 * @property int                             $id
 * @property int                             $survey_response_id
 * @property int                             $survey_question_id
 * @property int|null                        $value_int
 * @property string|null                     $value_option
 * @property string|null                     $value_text
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \App\Models\SurveyResponse      $response
 * @property \App\Models\SurveyQuestion      $question
 *
 * @method static Builder|SurveyAnswer newModelQuery()
 * @method static Builder|SurveyAnswer newQuery()
 * @method static Builder|SurveyAnswer query()
 *
 * @mixin \Eloquent
 */
class SurveyAnswer extends Eloquent
{
    use HasFactory;

    protected $fillable = [
        'survey_response_id', 'survey_question_id',
        'value_int', 'value_option', 'value_text',
    ];

    protected $casts = [
        'value_int' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function response(): BelongsTo
    {
        return $this->belongsTo(SurveyResponse::class, 'survey_response_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(SurveyQuestion::class, 'survey_question_id');
    }

    /**
     * The stored value, whichever column holds it.
     */
    public function value(): int|string|null
    {
        return $this->value_int ?? $this->value_option ?? $this->value_text;
    }

    /**
     * Human-readable value for admin display and CSV export.
     */
    public function displayValue(): string
    {
        if ($this->value_int !== null) {
            return (string) $this->value_int;
        }

        if ($this->value_option !== null) {
            return $this->question?->labelForOption($this->value_option) ?? $this->value_option;
        }

        return (string) ($this->value_text ?? '');
    }
}
