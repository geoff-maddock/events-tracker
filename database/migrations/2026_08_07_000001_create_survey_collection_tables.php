<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Collection side of the proactive feedback survey engine (issue #1998).
 *
 * Invitations are the targeting/lifecycle artifact: generated in batches,
 * deduped by unique index, expirable, and frequently terminal without ever
 * producing a response. Responses are the content artifact: immutable,
 * carrying visibility, exported, potentially shown publicly. Hence two tables
 * rather than one wide mostly-null one.
 *
 * NOTE ON TYPES: users.id, events.id and visibilities.id are `int unsigned`
 * in this schema, not bigint. foreignId() would emit `bigint unsigned` and
 * the FK constraint would fail to create — so references into legacy tables
 * use unsignedInteger() + an explicit foreign(). Only FKs between two new
 * tables use foreignId()/constrained().
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('survey_invitations')) {
            Schema::create('survey_invitations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('survey_campaign_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('user_id');

                // Morph-map key + id of what the invitation is about. Singular
                // lowercase keys per the map in AppServiceProvider ('event').
                //
                // NOT NULL with ''/0 defaults on purpose: MySQL treats NULLs as
                // distinct in a unique index, so nullable columns would make the
                // dedupe constraint below a no-op for subject-less campaigns.
                $table->string('subject_type', 64)->default('');
                $table->unsignedInteger('subject_id')->default(0);

                // Opaque route key. Prevents enumeration, and is the handle a
                // future emailed feedback link will use.
                $table->string('token', 40)->unique();

                // pending -> shown -> completed | dismissed | expired
                // Internal state machine values, deliberately not a lookup table:
                // they are never admin-managed or form-selectable.
                $table->string('status', 20)->default('pending');

                // app | email — which delivery path created this invitation.
                $table->string('source', 20)->default('app');

                $table->timestamp('shown_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('dismissed_at')->nullable();
                $table->timestamp('snoozed_until')->nullable();
                $table->timestamp('expires_at')->nullable();

                $table->timestamps();

                $table->unique(
                    ['survey_campaign_id', 'user_id', 'subject_type', 'subject_id'],
                    'survey_invitations_target_unique'
                );

                // The hot lookup: "does this user have anything pending?"
                $table->index(['user_id', 'status'], 'survey_invitations_user_status_index');
                $table->index(['subject_type', 'subject_id'], 'survey_invitations_subject_index');
                $table->index(['status', 'expires_at'], 'survey_invitations_expiry_index');

                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('survey_responses')) {
            Schema::create('survey_responses', function (Blueprint $table) {
                $table->id();

                // Nullable so an unsolicited response (someone following a link
                // with no invitation) is representable later.
                $table->foreignId('survey_invitation_id')->nullable()->unique()
                    ->constrained()->nullOnDelete();

                // Campaign and subject are denormalized off the invitation so
                // admin listing, filtering, export and any public display never
                // need to join invitations.
                $table->foreignId('survey_campaign_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('user_id');
                $table->string('subject_type', 64)->default('');
                $table->unsignedInteger('subject_id')->default(0);

                // Visibility::VISIBILITY_PRIVATE by default; the user may opt in
                // to VISIBILITY_PUBLIC per response.
                $table->unsignedInteger('visibility_id')->default(2);

                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();

                $table->index(['survey_campaign_id', 'submitted_at'], 'survey_responses_campaign_submitted_index');
                $table->index(['subject_type', 'subject_id'], 'survey_responses_subject_index');
                $table->index('visibility_id', 'survey_responses_visibility_index');

                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('visibility_id')->references('id')->on('visibilities');
            });
        }

        if (! Schema::hasTable('survey_answers')) {
            Schema::create('survey_answers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('survey_response_id')->constrained()->cascadeOnDelete();
                $table->foreignId('survey_question_id')->constrained()->cascadeOnDelete();

                // Typed columns rather than one JSON value: the admin payoff is
                // AVG(value_int) per question and GROUP BY value_option, both of
                // which are trivial indexed SQL here. A multi_choice answer is
                // stored as N rows, which is what the group-by wants anyway.
                $table->smallInteger('value_int')->nullable();
                $table->string('value_option', 64)->nullable();
                $table->text('value_text')->nullable();

                $table->timestamps();

                $table->index('survey_response_id', 'survey_answers_response_index');
                $table->index(['survey_question_id', 'value_option'], 'survey_answers_question_option_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_answers');
        Schema::dropIfExists('survey_responses');
        Schema::dropIfExists('survey_invitations');
    }
};
