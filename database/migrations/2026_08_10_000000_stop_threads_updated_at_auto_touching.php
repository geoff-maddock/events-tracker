<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * `threads.updated_at` carries MySQL's `ON UPDATE CURRENT_TIMESTAMP` (a legacy
 * schema attribute shared by ~70 columns in this database). That makes *any*
 * write to a thread row restamp it as freshly updated — including the view
 * counter increment on every page load, which is why a thread's last activity
 * moved whenever someone merely read it.
 *
 * Laravel writes updated_at explicitly on every model save, so the database
 * side of this is redundant; dropping it hands the column back to the
 * application, which is the only layer that knows what counts as activity.
 *
 * Scoped to `threads` on purpose — the other tables keep the legacy attribute
 * until something actually depends on changing them.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE `threads` MODIFY `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `threads` MODIFY `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }
};
