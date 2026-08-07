<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Definition side of the proactive feedback survey engine (issue #1998).
 *
 * Campaigns and their questions are seeded rows, not config arrays, so that
 * survey_answers can carry a stable FK to a question and the admin summary
 * can aggregate in SQL. Adding a new campaign is a seeder block, not a
 * migration — that is the point of this split.
 *
 * The collection side (invitations/responses/answers) lands in the sibling
 * migration and FKs into these tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('survey_campaigns')) {
            Schema::create('survey_campaigns', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 64)->unique();
                $table->string('name', 191);
                $table->text('intro')->nullable();

                // Morph-map key of the expected subject ('event', 'entity', …).
                // Null means the campaign has no subject, e.g. a general poll.
                $table->string('subject_type', 64)->nullable();

                $table->boolean('is_active')->default(true)->index();

                // Arbitration when a user has more than one pending invitation.
                $table->smallInteger('priority')->default(0);

                $table->unsignedSmallInteger('expires_after_days')->nullable()->default(30);

                // Visibility::VISIBILITY_PRIVATE — admin-only unless the user opts in.
                $table->unsignedInteger('default_visibility_id')->default(2);

                $table->timestamps();
            });
        }

        if (! Schema::hasTable('survey_questions')) {
            Schema::create('survey_questions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('survey_campaign_id')->constrained()->cascadeOnDelete();

                // Stable identifier used by the API payload and CSV export.
                // Answers are submitted keyed by this, never by autoincrement id.
                $table->string('key', 64);

                $table->unsignedSmallInteger('sort')->default(0);

                // rating | single_choice | multi_choice | text
                $table->string('type', 20);

                $table->string('prompt', 512);
                $table->string('help', 512)->nullable();

                // [{"value": "sound", "label": "Sound & production"}, …]
                // Kept as JSON rather than a sixth table: options are display
                // labels over a stable string value, and aggregation joins on
                // the indexed survey_answers.value_option, never on an options row.
                $table->json('options')->nullable();

                // Rating bounds. Null for non-rating types.
                $table->smallInteger('min_value')->nullable();
                $table->smallInteger('max_value')->nullable();

                $table->boolean('is_required')->default(false);
                $table->boolean('is_active')->default(true);

                $table->timestamps();

                $table->unique(['survey_campaign_id', 'key'], 'survey_questions_campaign_key_unique');
                $table->index(['survey_campaign_id', 'sort'], 'survey_questions_campaign_sort_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_questions');
        Schema::dropIfExists('survey_campaigns');
    }
};
