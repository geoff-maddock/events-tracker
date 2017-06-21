<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateForumsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        Schema::create('forums', function(Blueprint $table)
        {
            $table->increments('id');
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->integer('visibility_id')->unsigned()->nullable();
            $table->integer('sort_order');
            $table->boolean('is_active')->default(1);
            $table->integer('created_by')->default(1);
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('threads', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('forum_id')->unsigned()->index();
            $table->foreign('forum_id')->references('id')->on('forums')->nullable();
            $table->integer('thread_category_id')->unsigned()->nullable();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->text('body');
            $table->boolean('allow_html')->default(0);
            $table->integer('visibility_id')->unsigned()->nullable();
            $table->integer('recipient_id')->nullable();
            $table->integer('sort_order');
            $table->boolean('is_edittable')->default(1);
            $table->integer('likes');
            $table->integer('views');
            $table->boolean('is_active')->default(1);
            $table->integer('created_by')->default(1);
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('thread_categories', function(Blueprint $table)
        {
            $table->increments('id');
            $table->string('name');
            $table->integer('forum_id')->unsigned()->index();
            $table->foreign('forum_id')->references('id')->on('forums')->nullable();
            $table->timestamps();
        });

        Schema::create('content_types', function(Blueprint $table)
        {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('posts', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('thread_id')->unsigned()->index();
            $table->foreign('thread_id')->references('id')->on('threads')->nullable();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->text('body');
            $table->boolean('allow_html')->default(0);
            $table->integer('content_type_id')->unsigned()->nullable();
            $table->integer('visibility_id')->unsigned()->nullable();
            $table->integer('recipient_id')->nullable();
            $table->integer('reply_to')->nullable();
            $table->integer('likes');
            $table->integer('views');
            $table->boolean('is_active')->default(1);
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('event_thread', function(Blueprint $table)
        {
            $table->integer('event_id')->unsigned()->index();
#            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->integer('thread_id')->unsigned()->index();
#            $table->foreign('thread_id')->references('id')->on('threads')->onDelete('cascade');
            $table->timestamps();
        });




        Schema::create('tag_thread', function(Blueprint $table)
        {
            $table->integer('tag_id')->unsigned()->index();
#            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
            $table->integer('thread_id')->unsigned()->index();
#            $table->foreign('thread_id')->references('id')->on('threads')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('post_tag', function(Blueprint $table)
        {
            $table->integer('tag_id')->unsigned()->index();
#            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
            $table->integer('post_id')->unsigned()->index();
#            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
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
        Schema::drop('event_thread');
        Schema::drop('entity_thread');
        Schema::drop('tag_thread');
        Schema::drop('post_tag');
        Schema::drop('posts');
        Schema::drop('thread_categories');
        Schema::drop('content_types');
        Schema::drop('threads');
        Schema::drop('forums');

    }
}
