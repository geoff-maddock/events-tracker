<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLocationsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('location_types', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('name');
			$table->timestamps();
		});

		Schema::create('locations', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('name');
			$table->string('slug');
			$table->string('attn')->nullable();;
			$table->string('address_one')->nullable();;
			$table->string('address_two')->nullable();;
			$table->string('city')->nullable();;
			$table->string('neighborhood')->nullable();;
			$table->string('state')->nullable();;
			$table->string('postcode')->nullable();;
			$table->string('country')->nullable();;
			$table->decimal('latitude', 11, 8)->nullable();;
			$table->decimal('longitude', 11, 8)->nullable();;
			$table->integer('location_type_id')->unsigned()->index();
			$table->foreign('location_type_id')->references('id')->on('location_types')->onDelete('cascade');
			$table->integer('entity_id')->unsigned()->index();
			$table->foreign('entity_id')->references('id')->on('entities')->onDelete('cascade');
			$table->integer('capacity');
			$table->integer('created_by')->default(1);
			$table->integer('updated_by')->nullable();
			$table->string('map_url')->nullable();
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
		Schema::drop('locations');
		Schema::drop('location_types');
	}

}
