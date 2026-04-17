<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('blog_photo', function (Blueprint $table) {
            $table->unsignedInteger('blog_id');
            $table->unsignedInteger('photo_id');
            $table->tinyInteger('is_primary')->default(0);
            $table->tinyInteger('is_approved')->default(0);
            $table->timestamps();

            $table->index('blog_id', 'blog_photo_blog_id_index');
            $table->index('photo_id', 'blog_photo_photo_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_photo');
    }
};
