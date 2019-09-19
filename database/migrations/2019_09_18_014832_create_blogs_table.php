<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBlogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('slug');
            $table->longText('body');
            $table->integer('menu_parent_id')->unsigned()->nullable();
            $table->integer('visibility_id')->unsigned()->nullable();
            $table->timestamps();
        });

        Schema::create('blogs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('slug');
            $table->longText('body');
            $table->integer('menu_id')->unsigned()->nullable();
            $table->integer('content_type_id')->unsigned()->nullable();
            $table->integer('visibility_id')->unsigned()->nullable();
            $table->integer('sort_order');
            $table->boolean('is_active')->default(1);
            $table->boolean('is_admin')->default(0);
            $table->boolean('allow_html')->default(0);
            $table->integer('created_by')->default(1);
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('blog_tag', function(Blueprint $table)
        {
            $table->integer('blog_id')->unsigned()->index();
            $table->foreign('blog_id')->references('id')->on('blogs')->onDelete('cascade');
            $table->integer('tag_id')->unsigned()->index();
            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('blog_entity', function(Blueprint $table)
        {
            $table->integer('blog_id')->unsigned()->index();
            $table->foreign('blog_id')->references('id')->on('blogs')->onDelete('cascade');
            $table->integer('entity_id')->unsigned()->index();
            $table->foreign('entity_id')->references('id')->on('entities')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('menus');
        Schema::dropIfExists('blogs');
        Schema::dropIfExists('blog_entity');
        Schema::dropIfExists('blog_tag');
    }
}
