<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Discord auto-repost (issue #2058).
 *
 * Three tables:
 *
 *  - discord_targets: one row per Discord channel, holding the incoming
 *    webhook URL (encrypted) plus which modes it wants and how it presents.
 *  - discord_target_criteria: the per-target include/exclude filters that
 *    decide which events reach which channel. One typed table rather than
 *    five pivots — the criteria kinds all point at legacy int-keyed tables,
 *    and a sixth kind should be a validation-rule change, not a migration.
 *  - discord_posts: the ledger. Its unique index is the idempotency
 *    mechanism for every mode, not merely a report of what was sent.
 *
 * NOTE ON TYPES: events.id, users.id, tags.id and entities.id are
 * `int unsigned` in this schema, not bigint. foreignId() would emit
 * `bigint unsigned` and the FK constraint would fail to create — so
 * references into legacy tables use unsignedInteger() + an explicit
 * foreign(). Only FKs between two new tables use foreignId()/constrained().
 *
 * Why not reuse event_shares: it has no target column (and this whole feature
 * is per-target), it already holds duplicate (event_id, platform) rows from
 * Instagram so the dedupe index could not be added retroactively, and it
 * encodes failure as posted_at IS NULL — which under a unique key would
 * permanently block retries of a failed post.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('discord_targets')) {
            Schema::create('discord_targets', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->id();
                $table->string('name', 96);
                $table->string('slug', 96)->unique();

                // The webhook URL is a bearer credential — anyone holding it can
                // post as this channel. Stored under the 'encrypted' cast, so
                // this is ciphertext and needs far more room than the ~120 char
                // plaintext. Encryption is non-deterministic, so the column
                // itself cannot be unique-indexed; the sha256 fingerprint below
                // is what stops the same channel being added twice.
                $table->text('webhook_url');
                $table->char('webhook_fingerprint', 64)->nullable()->unique();

                // The numeric id segment of the URL. Safe to display, unlike
                // the token segment that follows it.
                $table->string('webhook_id', 32)->nullable();

                $table->boolean('is_enabled')->default(true);

                // Explicit firehose opt-in. A target with no criteria and
                // match_all = false matches nothing, which fails safe.
                $table->boolean('match_all')->default(false);
                $table->boolean('requires_photo')->default(true);

                // Per-mode opt-in.
                $table->boolean('post_on_create')->default(true);
                $table->boolean('post_reminders')->default(false);
                $table->boolean('post_digest')->default(false);
                $table->boolean('allow_manual')->default(true);

                // Days before start_at, e.g. [7, 1]. Null falls back to
                // config('discord.default_reminder_offsets').
                $table->json('reminder_offsets')->nullable();

                // Carbon dayOfWeek (0 = Sunday) and hour in the app timezone.
                // The digest command runs hourly and serves only the targets
                // whose day+hour match, which gives per-target scheduling
                // without a cron entry per channel.
                $table->tinyInteger('digest_day')->default(4);
                $table->tinyInteger('digest_hour')->default(10);
                $table->smallInteger('digest_window_days')->default(7);

                // Presentation overrides. mention is sent as the message
                // content above the embed, e.g. '@here' or '<@&roleid>'.
                $table->string('mention', 64)->nullable();
                $table->string('username', 80)->nullable();
                $table->string('avatar_url', 255)->nullable();
                $table->unsignedInteger('embed_color')->nullable();

                // Health. consecutive_failures counts permanent failures only.
                $table->smallInteger('consecutive_failures')->default(0);
                $table->timestamp('last_success_at')->nullable();
                $table->timestamp('last_failure_at')->nullable();
                $table->string('last_error', 255)->nullable();
                $table->timestamp('disabled_at')->nullable();
                $table->string('disabled_reason', 255)->nullable();

                $table->unsignedInteger('created_by')->nullable();
                $table->unsignedInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['is_enabled', 'post_on_create']);
                $table->index(['is_enabled', 'post_digest', 'digest_day', 'digest_hour'], 'discord_targets_digest_index');

                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('discord_target_criteria')) {
            Schema::create('discord_target_criteria', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->id();
                $table->foreignId('discord_target_id')->constrained()->cascadeOnDelete();

                // tag | entity | event_type | series | venue | promoter.
                // criteria_id points into the corresponding legacy table, so
                // it is unsignedInteger and carries no FK (the target table
                // varies by row).
                $table->string('criteria_type', 24);
                $table->unsignedInteger('criteria_id');

                // include | exclude. Within a type includes are OR'd, across
                // types they are AND'd, and any exclude match vetoes.
                $table->string('mode', 8)->default('include');

                $table->timestamps();

                $table->unique(['discord_target_id', 'criteria_type', 'criteria_id'], 'discord_target_criteria_unique');
                $table->index(['criteria_type', 'criteria_id']);
            });
        }

        if (! Schema::hasTable('discord_posts')) {
            Schema::create('discord_posts', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->id();
                $table->foreignId('discord_target_id')->constrained()->cascadeOnDelete();

                // event_id and reason_key are NOT NULL with 0/'' defaults on
                // purpose: MySQL treats NULLs as distinct in a unique index, so
                // nullable columns would silently disable the dedupe below for
                // digest and test rows. No FK on event_id — a digest row is not
                // about one event, and event_shares sets the same precedent.
                $table->unsignedInteger('event_id')->default(0);

                // create | reminder | digest | manual | test
                $table->string('reason', 16);

                // Distinguishes repeats of the same reason: '' for create,
                // the offset ('7') for reminders, the ISO week ('2026-W32')
                // for digests, user+minute for a deliberate manual repost.
                $table->string('reason_key', 32)->default('');

                // pending | sent | failed | skipped
                $table->string('status', 16)->default('pending');

                $table->string('message_id', 32)->nullable();
                $table->string('channel_id', 32)->nullable();

                // Hash of the sent payload, so a future edit-aware pass can
                // tell whether an already-posted message is now stale.
                $table->char('payload_hash', 40)->nullable();

                $table->unsignedTinyInteger('attempts')->default(0);
                $table->string('error', 500)->nullable();
                $table->timestamp('posted_at')->nullable();

                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();

                $table->unique(['discord_target_id', 'event_id', 'reason', 'reason_key'], 'discord_posts_dedupe_unique');
                $table->index('event_id');
                $table->index(['status', 'created_at']);

                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        // Activity::log() resolves an integer action through Action::findOrFail(),
        // so the row has to exist before the first Discord post is logged. It is
        // inserted here rather than left to ActionsTableSeeder because that
        // seeder opens with a delete() of the whole table and will never be
        // re-run against an existing install.
        if (Schema::hasTable('actions')) {
            DB::table('actions')->updateOrInsert(
                ['id' => 20],
                [
                    'name' => 'Discord Post',
                    'description' => 'Event posted to Discord',
                    'order' => 0,
                    'is_active' => 1,
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('discord_posts');
        Schema::dropIfExists('discord_target_criteria');
        Schema::dropIfExists('discord_targets');

        if (Schema::hasTable('actions')) {
            DB::table('actions')->where('id', 20)->where('name', 'Discord Post')->delete();
        }
    }
};
