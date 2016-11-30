<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLinksTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('links', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('url')->nullable();
			$table->string('text')->nullable();
			$table->string('image')->nullable();
			$table->string('title')->nullable();
			$table->string('api')->nullable();
			$table->boolean('confirm')->default(0);
			$table->boolean('is_primary')->default(0);
			$table->timestamps();
		});


		Schema::create('entity_link', function(Blueprint $table)
		{
			$table->integer('entity_id')->unsigned()->index();
			$table->foreign('entity_id')->references('id')->on('entities')->onDelete('cascade');
			$table->integer('link_id')->unsigned()->index();
			$table->foreign('link_id')->references('id')->on('links')->onDelete('cascade');
			$table->timestamps();
		});

		Schema::create('event_link', function(Blueprint $table)
		{
			$table->integer('event_id')->unsigned()->index();
			$table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
			$table->integer('link_id')->unsigned()->index();
			$table->foreign('link_id')->references('id')->on('links')->onDelete('cascade');
			$table->timestamps();
		});


		Schema::create('series_link', function(Blueprint $table)
		{
			$table->integer('series_id')->unsigned()->index();
			$table->foreign('series_id')->references('id')->on('series')->onDelete('cascade');
			$table->integer('link_id')->unsigned()->index();
			$table->foreign('link_id')->references('id')->on('links')->onDelete('cascade');
			$table->timestamps();
		});

		Schema::create('link_user', function(Blueprint $table)
		{
			$table->integer('user_id')->unsigned()->index();
			$table->foreign('user_id')->references('id')->on('events')->onDelete('cascade');
			$table->integer('link_id')->unsigned()->index();
			$table->foreign('link_id')->references('id')->on('links')->onDelete('cascade');
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
		Schema::drop('links');
		Schema::drop('event_link');
		Schema::drop('entity_link');
		Schema::drop('series_link');
		Schema::drop('link_user');
	}

}
