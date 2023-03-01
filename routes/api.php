<?php

use App\Models\Entity;
use App\Models\Series;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.basic')->name('api.')->group(function () {
    Route::get('events/reset', ['as' => 'events.reset', 'uses' => 'Api\EventsController@reset']);
    Route::get('events/rpp-reset', ['as' => 'events.rppReset', 'uses' => 'Api\EventsController@rppReset']);
    Route::resource('events', 'Api\EventsController');

    Route::get('entities/reset', ['as' => 'entities.reset', 'uses' => 'Api\EntitiesController@reset']);
    Route::get('entities/rpp-reset', ['as' => 'entities.rppReset', 'uses' => 'Api\EntitiesController@rppReset']);
    Route::resource('entities', 'Api\EntitiesController');

    Route::match(['get', 'post'], 'entity-types/filter', ['as' => 'entityType.filter', 'uses' => 'Api\EntityTypesController@filter']);
    Route::get('entity-types/reset', ['as' => 'entity-types.reset', 'uses' => 'Api\EntityTypesController@reset']);
    Route::get('entity-types/rpp-reset', ['as' => 'entity-types.rppReset', 'uses' => 'Api\EntityTypesController@rppReset']);
    Route::resource('entity-types', 'Api\EntityTypesController');

    Route::match(['get', 'post'], 'entity-statuses/filter', ['as' => 'entityStatus.filter', 'uses' => 'Api\EntityStatusesController@filter']);
    Route::get('entity-statuses/reset', ['as' => 'entity-statuses.reset', 'uses' => 'Api\EntityStatusesController@reset']);
    Route::get('entity-statuses/rpp-reset', ['as' => 'entity-statuses.rppReset', 'uses' => 'Api\EntityStatusesController@rppReset']);
    Route::resource('entity-statuses', 'Api\EntityStatusesController');

    Route::get('series/reset', ['as' => 'series.reset', 'uses' => 'Api\SeriesController@reset']);
    Route::get('series/rpp-reset', ['as' => 'series.rppReset', 'uses' => 'Api\SeriesController@rppReset']);
    Route::resource('series', 'Api\SeriesController');

    Route::match(['get', 'post'], 'tags/filter', ['as' => 'tags.filter', 'uses' => 'Api\TagsController@filter']);
    Route::get('tags/reset', ['as' => 'tags.reset', 'uses' => 'Api\TagsController@reset']);
    Route::get('tags/rpp-reset', ['as' => 'tags.rppReset', 'uses' => 'Api\TagsController@rppReset']);
    Route::resource('tags', 'Api\TagsController');

    Route::match(['get', 'post'], 'links/filter', ['as' => 'links.filter', 'uses' => 'Api\LinksController@filter']);
    Route::get('links/reset', ['as' => 'links.reset', 'uses' => 'Api\LinksController@reset']);
    Route::get('links/rpp-reset', ['as' => 'links.rppReset', 'uses' => 'Api\LinksController@rppReset']);
    Route::resource('links', 'Api\LinksController');

    Route::match(['get', 'post'], 'users/filter', ['as' => 'users.filter', 'uses' => 'Api\UsersController@filter']);
    Route::get('users/reset', ['as' => 'users.reset', 'uses' => 'Api\UsersController@reset']);
    Route::get('users/rpp-reset', ['as' => 'users.rppReset', 'uses' => 'Api\UsersController@rppReset']);
    Route::resource('users', 'Api\UsersController');

});

// routes protected by the shield middleware
Route::middleware('shield')->name('shield.')->group(function () {
});

// calendar routes - these are used by the web app for dynamic loading
Route::get('calendar-events', 'EventsController@calendarEventsApi')->name('calendarEvents.api');
Route::get('tag-calendar-events', 'EventsController@tagCalendarEventsApi')->name('tagCalendarEvents.api');