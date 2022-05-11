<?php

use App\Models\Entity;
use App\Models\Event;
use App\Models\Series;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('events', function () {
    return Event::all();
});

// This endpoint collects series and events and returns them as JSON
// Route::get('calendar-events', 'EventsController@calendarEventsApi')->name('calendarEvents.api')->middleware('auth.basic');
Route::get('calendar-events', 'EventsController@calendarEventsApi')->name('calendarEvents.api');
Route::get('tag-calendar-events', 'EventsController@tagCalendarEventsApi')->name('tagCalendarEvents.api');

Route::get('events/{id}', function ($id) {
    return Event::find($id);
});

Route::get('entities', function () {
    return Entity::allOrdered();
});

Route::get('entities/{id}', function ($id) {
    return Entity::find($id);
});

Route::get('series', function () {
    return Series::all();
});

Route::get('series/{id}', function ($id) {
    return Series::find($id);
});
