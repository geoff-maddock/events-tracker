<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * New signups start with only the weekly digest (#2083).
 *
 * Profiles are created empty at registration, so the column defaults decide
 * which emails a new account gets. Daily, instant and forum mail now default
 * to off; weekly stays on. Existing rows are not touched.
 *
 * ALTER ... SET DEFAULT rather than ->change(): it changes only the default,
 * so it can't trip over the columns' nullability, which differs between
 * older installs (NULL) and the current schema dump (NOT NULL).
 */
return new class extends Migration
{
    private const OFF_BY_DEFAULT = ['setting_daily_update', 'setting_instant_update', 'setting_forum_update'];

    public function up(): void
    {
        foreach (self::OFF_BY_DEFAULT as $column) {
            DB::statement("ALTER TABLE `profiles` ALTER COLUMN `$column` SET DEFAULT 0");
        }
    }

    public function down(): void
    {
        foreach (self::OFF_BY_DEFAULT as $column) {
            DB::statement("ALTER TABLE `profiles` ALTER COLUMN `$column` SET DEFAULT 1");
        }
    }
};
