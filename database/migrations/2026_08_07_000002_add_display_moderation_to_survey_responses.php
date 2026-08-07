<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin moderation gate for publicly displaying feedback (issue #1998).
 *
 * `visibility_id` already records what the *submitter* consented to. This adds
 * what the *admin* decided, deliberately as a separate column rather than a
 * fourth visibility value: the two are independent facts, and collapsing them
 * would make an admin approval silently overwrite user consent.
 *
 * A response is only ever displayable when both agree — see
 * SurveyResponse::scopeDisplayable(). Nothing renders public feedback yet;
 * this is the gate that future display code must go through.
 *
 * NOTE ON TYPES: users.id is `int unsigned` in this schema, so approved_by
 * uses unsignedInteger() + explicit foreign(), matching the sibling migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('survey_responses')) {
            return;
        }

        Schema::table('survey_responses', function (Blueprint $table) {
            if (! Schema::hasColumn('survey_responses', 'display_status')) {
                // pending -> approved | rejected. Admin-facing but never
                // user-selectable, so a string column rather than a lookup
                // table, matching survey_invitations.status.
                $table->string('display_status', 20)
                    ->default('pending')
                    ->after('visibility_id');
            }

            if (! Schema::hasColumn('survey_responses', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('display_status');
            }

            if (! Schema::hasColumn('survey_responses', 'approved_by')) {
                $table->unsignedInteger('approved_by')->nullable()->after('approved_at');
            }
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            // The display lookup is always "public AND approved", so index the
            // pair rather than display_status alone.
            $table->index(['visibility_id', 'display_status'], 'survey_responses_display_index');
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('survey_responses')) {
            return;
        }

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropIndex('survey_responses_display_index');
            $table->dropColumn(['display_status', 'approved_at', 'approved_by']);
        });
    }
};
