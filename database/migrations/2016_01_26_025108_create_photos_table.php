<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePhotosTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('photos', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('name');
			$table->string('thumbnail');
			$table->string('path');
			$table->string('caption');
			$table->boolean('is_public')->default('1');
			$table->boolean('is_primary')->default('0');
			$table->boolean('is_approved')->default('0');
			$table->integer('created_by')->default(1);
			$table->integer('updated_by')->nullable();
			$table->timestamps();
		});

		Schema::create('event_photo', function(Blueprint $table)
		{
			$table->integer('event_id')->unsigned()->index();
			$table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
			$table->integer('photo_id')->unsigned()->index();
			$table->foreign('photo_id')->references('id')->on('photos')->onDelete('cascade');
			$table->boolean('is_primary')->default('0');
			$table->boolean('is_approved')->default('0');
			$table->timestamps();
		});

		Schema::create('photo_user', function(Blueprint $table)
		{
			$table->integer('user_id')->unsigned()->index();
			$table->foreign('user_id')->references('id')->on('events')->onDelete('cascade');
			$table->integer('photo_id')->unsigned()->index();
			$table->foreign('photo_id')->references('id')->on('photos')->onDelete('cascade');
			$table->boolean('is_primary')->default('0');
			$table->boolean('is_approved')->default('0');
			$table->timestamps();
		});

		Schema::create('entity_photo', function(Blueprint $table)
		{
			$table->integer('entity_id')->unsigned()->index();
			$table->foreign('entity_id')->references('id')->on('entities')->onDelete('cascade');
			$table->integer('photo_id')->unsigned()->index();
			$table->foreign('photo_id')->references('id')->on('photos')->onDelete('cascade');
			$table->boolean('is_primary')->default('0');
			$table->boolean('is_approved')->default('0');
			$table->timestamps();
		});

		Schema::create('photo_series', function(Blueprint $table)
		{
			$table->integer('series_id')->unsigned()->index();
			$table->foreign('series_id')->references('id')->on('series')->onDelete('cascade');
			$table->integer('photo_id')->unsigned()->index();
			$table->foreign('photo_id')->references('id')->on('photos')->onDelete('cascade');
			$table->boolean('is_primary')->default('0');
			$table->boolean('is_approved')->default('0');
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
		Schema::drop('photo_user');
		Schema::drop('entity_photo');
		Schema::drop('event_photo');
		Schema::drop('photo_series');
		Schema::drop('photos');
	}

}
