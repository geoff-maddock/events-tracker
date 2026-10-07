<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-list opt-outs for addresses that are not users (#2103).
 *
 * NotifyEntities mails entity contact addresses: people who never registered
 * and have no profile flags to turn off. Their opt-out lives here rather than
 * in email_suppressions because a suppression blocks *every* message,
 * including the verification and password-reset mail they would need if they
 * later signed up. An opt-out only stops the one list.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_opt_outs', function (Blueprint $table) {
            $table->id();

            // Lowercased, via EmailSuppression::normalize().
            $table->string('email');
            $table->string('list', 32);

            $table->timestamps();

            $table->unique(['email', 'list']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_opt_outs');
    }
};
