<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Events\EventCreated;
use App\Events\EventUpdated;
use App\Event;
use App\Entity;
use App\Series;

Route::get('events', function () {
  return Event::all();
});

Route::get('events/{id}', function($id) {
    return Event::find($id);
});

Route::get('entities', function () {
    return Entity::all();
});

Route::get('entities/{id}', function($id) {
    return Entity::find($id);
});


Route::get('series', function () {
    return Series::all();
});

Route::get('series/{id}', function($id) {
    return Series::find($id);
});

Route::post('login', 'Auth\LoginController@login');

Route::middleware('auth:api')
    ->get('user', function (Request $request) {
        return $request->user();
    });
