<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a survey question depend on the answer to an earlier one (issue #1998).
 *
 * Needed so the post-event campaign can ask "did you make it?" up front and
 * branch: attendees get the how-was-it questions, no-shows get asked why
 * instead. Deliberately generic rather than special-cased, because the
 * deferred "why did you skip this event" campaign needs the same mechanism.
 *
 * A question with a null depends_on_key always shows.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('survey_questions')) {
            return;
        }

        Schema::table('survey_questions', function (Blueprint $table) {
            if (! Schema::hasColumn('survey_questions', 'depends_on_key')) {
                // `key` of another question in the same campaign.
                $table->string('depends_on_key', 64)->nullable()->after('is_active');
            }

            if (! Schema::hasColumn('survey_questions', 'depends_on_value')) {
                // Value that question must hold for this one to apply.
                $table->string('depends_on_value', 64)->nullable()->after('depends_on_key');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('survey_questions')) {
            return;
        }

        Schema::table('survey_questions', function (Blueprint $table) {
            foreach (['depends_on_key', 'depends_on_value'] as $column) {
                if (Schema::hasColumn('survey_questions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
