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
$app->get('/users/', 'UserController@index');
$app->post('/users/', 'UserController@register');
$app->get('/users/{user_id}', 'UserController@show');
$app->put('/users/{user_id}', 'UserController@update');
$app->delete('/users/{user_id}', 'UserController@destroy');

// Request Access Tokens
$app->post('/users/signin', function() use ($app){
    return response()->json($app->make('oauth2-server.authorizer')->issueAccessToken());
});