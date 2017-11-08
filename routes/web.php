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
    $app->post('/', 'UserController@register');
    $app->post('/signin', 'UserController@signin');
});

$app->group(['prefix' => 'users', 'middleware' => 'auth'], function () use($app) {
    $app->post('/signout', 'UserController@signOut');
    $app->post('/{user_id}', 'UserController@update');
    //$app->delete('/{user_id}', 'UserController@destroy');

});

// Access Token
$app->group(['prefix' => 'api', 'middleware' => 'auth'], function () use($app) {
    //Request
    $app->post('/request', 'RequestController@getRequest');
    $app->post('/sendRequest', 'FCMController@getRequest');

    //Rating
    $app->post('/rating','RatingController@doVote');
});

// Request Access Tokens
/*$app->post('/users/signin', function() use ($app){
    return response()->json($app->make('oauth2-server.authorizer')->issueAccessToken());
});*/