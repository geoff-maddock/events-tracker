<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Events whose owner (created_by) was deleted pass to the site admin
 * (APP_SUPERUSER), matching what User's deleting hook now does for new
 * deletions.
 */
return new class extends Migration
{
    public function up(): void
    {
        $orphans = DB::table('events')->whereNotIn('created_by', DB::table('users')->select('id'));

        if (!$orphans->exists()) {
            return;
        }

        $adminId = (int) config('app.superuser');

        if ($adminId < 1 || !DB::table('users')->where('id', $adminId)->exists()) {
            echo PHP_EOL.'  APP_SUPERUSER is not an existing user; events of deleted users were left as they are.'.PHP_EOL;

            return;
        }

        $count = $orphans->update(['created_by' => $adminId]);

        echo PHP_EOL."  Reassigned {$count} event(s) of deleted users to user #{$adminId}.".PHP_EOL;
    }

    public function down(): void
    {
        // the previous owners no longer exist, so there is nothing to restore
    }
};
