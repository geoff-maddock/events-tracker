<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A display-size variant for hero images (#1932).
 *
 * Heroes served the main image (up to 2000px, or a full-size legacy JPEG)
 * into a slot that needs about 1200px. `large` is the 1200px WebP, or the
 * main image's own path when that is already no bigger. Null means not
 * generated yet: Photo::getStorageLarge() falls back to the main image, and
 * photos:generate-variants fills it in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->string('large')->nullable()->after('thumbnail');
        });
    }

    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn('large');
        });
    }
};
