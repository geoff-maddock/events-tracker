<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The "Unlisted" entity status (EntityStatus::UNLISTED = 4) exists in
 * production, but no migration or seeder created it, so fresh installs and
 * the test database lacked it (#2255). Adds it where it is missing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('entity_statuses')->where('id', 4)->exists()) {
            return;
        }

        DB::table('entity_statuses')->insert([
            'id' => 4,
            'name' => 'Unlisted',
            'created_at' => '2024-05-22 21:04:32',
            'updated_at' => '2024-05-22 21:04:32',
        ]);
    }

    public function down(): void
    {
        // left in place: entities may use it, and production had it before this migration
    }
};
