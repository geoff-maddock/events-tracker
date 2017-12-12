<?php

use Faker\Generator as Faker;

$factory->define(App\Profile::class, function (Faker $faker) {

        $user = factory('App\User')->create();
        return [
            'user_id' => $user->id,
            'first_name' => $faker->name,
            'last_name' => $faker->name
        ];

});
