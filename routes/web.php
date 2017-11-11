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
    //$app->delete('/{user_id}', 'UserController@destroy');

});

// Access Token
$app->group(['prefix' => 'api', 'middleware' => 'auth'], function () use($app) {
    //Request
    $app->post('/request', 'RequestController@pushInfomation');
    $app->post('/send-request', 'RequestController@sendRequestToAnotherOne');
    $app->post('/accept-request', 'RequestController@acceptRequest');
    $app->post('/deny-request', 'RequestController@denyRequest');

    //Rating
    $app->post('/rating','RatingController@doVote');
});

// Request Access Tokens
/*$app->post('/users/signin', function() use ($app){
    return response()->json($app->make('oauth2-server.authorizer')->issueAccessToken());
});*/