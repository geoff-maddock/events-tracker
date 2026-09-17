<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Entity ownership moves from entities.created_by to its own table (#2147),
 * so a claimed page can be transferred to someone else while created_by
 * stays as a record of who first added it.
 *
 * Every entity starts owned by its creator, so nothing changes on deploy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entity_owners', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('entity_id');
            $table->unsignedInteger('user_id');
            // the admin (or approved claim reviewer) who granted it; null for the backfill and creators
            $table->unsignedInteger('granted_by')->nullable();
            $table->timestamps();

            $table->unique(['entity_id', 'user_id']);
            $table->index('user_id');
            $table->foreign('entity_id')->references('id')->on('entities')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('granted_by')->references('id')->on('users')->nullOnDelete();
        });

        // join users so a created_by pointing at a deleted user is skipped
        DB::statement(
            'INSERT INTO entity_owners (entity_id, user_id, created_at, updated_at)
             SELECT entities.id, entities.created_by, NOW(), NOW()
             FROM entities
             JOIN users ON users.id = entities.created_by'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('entity_owners');
    }
};
