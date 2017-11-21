<?php
/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/
$factory->define(App\User::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => bcrypt(str_random(10)),
        'remember_token' => str_random(10),
    ];
});

$factory->define(App\Series::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->name,
        'slug' => $faker->name,
        'short' => $faker->paragraph,
        'description' => $faker->paragraph,
        'event_type_id' => rand(1,5),
        'visibility_id' => rand(1,3),
        'occurrence_type' => rand(1,3),
        'start_at' => now(),
        'is_active' => 1,
        'created_by' => 1
    ];
});

$factory->define(App\Forum::class, function (Faker\Generator $faker) {
    $user = factory('App\User')->create()->id;
    return [
        'name' => $faker->sentence,
        'slug' => $faker->sentence,
        'description' => $faker->sentence,
        'visibility_id' => 3,
        'is_active' => 1,
        'created_by' => 1
    ];
});

$factory->define(App\Thread::class, function (Faker\Generator $faker) {
	$user = factory('App\User')->create()->id;
	return [
		'forum_id' => 1,
        'created_by' => function() {
            return App\User::all()->last()->id;
        },
		'name' => $faker->sentence,
		'slug' => $faker->sentence,
		'body' => $faker->paragraph,
		'description' => $faker->paragraph,
		'visibility_id' => 3
	];
});

$factory->define(App\ThreadCategory::class, function (Faker\Generator $faker) {
    return [
        'forum_id' => 1,
        'name' => $faker->word
    ];
});

$factory->define(App\Post::class, function (Faker\Generator $faker) {
	return [
		'thread_id' => function() {
			return factory('App\Thread')->create()->id;
		},
		'created_by' => function() {
			return App\User::all()->last()->id;
		},
		'name' => $faker->sentence,
		'slug' => $faker->sentence,
		'body' => $faker->paragraph,
		'description' => $faker->paragraph,
		'visibility_id' => 1
	];
});