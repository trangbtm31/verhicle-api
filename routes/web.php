<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->get('/', function () use ($app) {
    return $app->version();
});

// Users
$app->group(['prefix' => 'users'], function () use($app) {
    $app->post('/register', 'UserController@register');
    $app->post('/signin', 'UserController@signin');
});

$app->group(['prefix' => 'users', 'middleware' => 'auth'], function () use($app) {
    $app->post('/signout', 'UserController@signOut');
    $app->post('/update', 'UserController@update');
    $app->post('/show', 'UserController@show');
    $app->post('/show-history', 'UserController@getUserHistory');
    $app->post('/add-to-favorite', 'UserController@addToFavorites');

});

// Access Token
$app->group(['prefix' => 'api', 'middleware' => 'auth'], function () use($app) {
    //Request
    $app->post('/request', 'JourneyController@pushInfomation');
    $app->post('/send-request', 'JourneyController@sendRequestToAnotherOne');
    $app->post('/confirm-request', 'JourneyController@confirmRequest');
    $app->post('/cancel-request', 'JourneyController@cancelRequest');

    //Journey
    $app->post('/start-the-trip', 'JourneyController@startTheTrip');
    $app->post('/end-the-trip', 'JourneyController@endTheTrip');
    $app->post('/cancel-the-trip', 'JourneyController@cancelTheTrip');
    $app->post('/get-active-request', 'JourneyController@getActiveRequest');

    //Rating
    $app->post('/rating','RatingController@doVote');
});

// Request Access Tokens
/*$app->post('/users/signin', function() use ($app){
    return response()->json($app->make('oauth2-server.authorizer')->issueAccessToken());
});*/