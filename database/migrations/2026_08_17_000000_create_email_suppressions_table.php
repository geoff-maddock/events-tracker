<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Email suppression list.
 *
 * The app has never consumed the ESP's bounce/complaint data, so an address
 * that hard-bounced years ago is still mailed on every digest run. Of the 158
 * addresses in the Mailgun bounce export, 24 are live users with all
 * notification settings enabled — guaranteed bounces on every send.
 *
 * That matters for the SES migration specifically: SES opens an account review
 * at a 5% bounce rate, and a first send to the current daily-digest population
 * would be ~3.7% from known-bad addresses alone, before any unknown address
 * fails, and with no reputation history to absorb it.
 *
 * Addresses are stored lowercased; EmailSuppression::normalize() is the single
 * place that decides what "same address" means, and the unique index enforces
 * it. `reason` records why (bounce/complaint/unsubscribe/manual) and `source`
 * records who told us (mailgun/ses/manual), so a future provider migration can
 * import a new list without losing the provenance of the old one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_suppressions', function (Blueprint $table) {
            $table->id();

            // Lowercased address. Unique so imports can upsert idempotently and
            // so the send-time lookup is a single indexed hit.
            $table->string('email')->unique();

            $table->string('reason', 32)->default('bounce');
            $table->string('source', 32)->default('manual');

            // SMTP code and message where the provider gave one. Free-text —
            // every provider words these differently.
            $table->string('code', 16)->nullable();
            $table->text('error')->nullable();

            // When the provider recorded the event, which is not when we
            // imported it.
            $table->timestamp('suppressed_at')->nullable();

            $table->timestamps();

            $table->index('reason');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_suppressions');
    }
};
