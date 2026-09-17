<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Self-serve requests to take over an entity page (#2148). An admin approves
 * or denies each one; approval transfers ownership in entity_owners.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entity_claims', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('entity_id');
            $table->unsignedInteger('user_id');
            // pending | approved | denied | withdrawn
            $table->string('status', 16)->default('pending');
            // how the claimant is affiliated with the entity
            $table->text('message');
            $table->string('evidence_url', 500)->nullable();
            $table->unsignedInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['entity_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->foreign('entity_id')->references('id')->on('entities')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entity_claims');
    }
};
