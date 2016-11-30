<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTagsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('tags', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('name');
			$table->integer('tag_type_id')->references('id')->on('event_type')->nullable();
			$table->timestamps();
		});

		Schema::create('event_tag', function(Blueprint $table)
		{
			$table->integer('event_id')->unsigned()->index();
			$table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
			$table->integer('tag_id')->unsigned()->index();
			$table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
			$table->timestamps();
		});

		Schema::create('series_tag', function(Blueprint $table)
		{
			$table->integer('series_id')->unsigned()->index();
			$table->foreign('series_id')->references('id')->on('series')->onDelete('cascade');
			$table->integer('tag_id')->unsigned()->index();
			$table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
			$table->timestamps();
		});

		Schema::create('entity_tag', function(Blueprint $table)
		{
			$table->integer('entity_id')->unsigned()->index();
			$table->foreign('entity_id')->references('id')->on('entities')->onDelete('cascade');
			$table->integer('tag_id')->unsigned()->index();
			$table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
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
		Schema::drop('series_tag');
		Schema::drop('event_tag');
		Schema::drop('entity_tag');
		Schema::drop('tags');

	}

}
