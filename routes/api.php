<?php

use App\Models\Entity;
use App\Models\Series;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::name('api.')->group(function () {
    Route::resource('events', 'Api\EventsController');
    Route::resource('entities', 'Api\EntitiesController');
    Route::resource('tags', 'Api\TagsController');
    Route::resource('series', 'Api\SeriesController');

    // This endpoint collects series and events and returns them as JSON
    // Route::get('calendar-events', 'EventsController@calendarEventsApi')->name('calendarEvents.api')->middleware('auth.basic');
    Route::get('calendar-events', 'EventsController@calendarEventsApi')->name('calendarEvents.api');
    Route::get('tag-calendar-events', 'EventsController@tagCalendarEventsApi')->name('tagCalendarEvents.api');

    Route::get('series', function () {
        return Series::all();
    });

    Route::get('series/{id}', function ($id) {
        return Series::find($id);
    });
});
