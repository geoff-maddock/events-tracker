<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nothing stopped the same response being stored twice: two "attend" requests
 * arriving together both found no row and both added one. Each extra row
 * counted as one more person attending. The repeats go (the earliest is kept)
 * and the three columns become unique.
 */
return new class extends Migration
{
    private const COLUMNS = ['event_id', 'user_id', 'response_type_id'];

    public function up(): void
    {
        if (Schema::hasIndex('event_responses', self::COLUMNS, 'unique')) {
            return;
        }

        // On a live site a new repeat can land between the delete and the index;
        // then both are simply done again.
        for ($attempt = 1; ; ++$attempt) {
            DB::statement(
                'DELETE later FROM event_responses later
                 INNER JOIN event_responses earlier
                    ON earlier.event_id = later.event_id
                   AND earlier.user_id = later.user_id
                   AND earlier.response_type_id = later.response_type_id
                   AND earlier.id < later.id'
            );

            try {
                Schema::table('event_responses', function (Blueprint $table) {
                    $table->unique(self::COLUMNS);
                });

                return;
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt >= 3) {
                    throw $e;
                }
            }
        }
    }

    /**
     * The removed repeats are not restored.
     */
    public function down(): void
    {
        // by name: a unique index that was already there under another name is not this migration's
        if (Schema::hasIndex('event_responses', 'event_responses_event_id_user_id_response_type_id_unique')) {
            Schema::table('event_responses', function (Blueprint $table) {
                $table->dropUnique(self::COLUMNS);
            });
        }
    }
};
