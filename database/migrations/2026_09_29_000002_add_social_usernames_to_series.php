<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * series.facebook_username / instagram_username / twitter_username exist on the
 * production database (and the series form and $fillable use them) but were
 * never in a migration or the schema dump, so fresh installs and the test
 * database lacked them. Add each one only where it's missing (#2180).
 */
return new class extends Migration
{
    private const COLUMNS = ['facebook_username', 'instagram_username', 'twitter_username'];

    public function up(): void
    {
        $previous = 'updated_by';

        foreach (self::COLUMNS as $column) {
            if (!Schema::hasColumn('series', $column)) {
                Schema::table('series', function (Blueprint $table) use ($column, $previous) {
                    $table->string($column, 64)->nullable()->after($previous);
                });
            }
            $previous = $column;
        }
    }

    public function down(): void
    {
        // the columns predate this migration on existing databases; leave them
    }
};
