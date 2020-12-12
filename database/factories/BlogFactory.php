<?php

use App\Models\Blog;
use App\Models\ContentType;
use Carbon\Carbon;
use Faker\Generator as Faker;

$factory->define(Blog::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'slug' => $faker->name,
        'body' => $faker->paragraph,
        'menu_id' => null,
        'content_type_id' => null,
        'visibility_id' => random_int(1, 3),
        'sort_order' => 0,
        'is_active' => $faker->boolean,
        'is_admin' => $faker->boolean,
        'allow_html' => $faker->boolean,
        'created_by' => random_int(1, 10),
        'updated_by' => random_int(1, 10),
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now()
    ];
});

$factory->state(App\Blog::class, 'plain_text', [
    'content_type_id' => ContentType::PLAIN_TEXT
]);

$factory->state(App\Blog::class, 'html', [
    'entity_type_id' => ContentType::HTML
]);
