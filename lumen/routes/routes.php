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

/** @var \Laravel\Lumen\Routing\Router $router */

$router->get('/', function () {
    return 'Purchase Gateway';
});

$router->group(['prefix' => '/'], function () use ($router) {
    require(__DIR__ . '/api.php');
});

$router->group(['prefix' => 'tests/'], function () use ($router) {
    $router->post('clientPostbackUrl[/{statusCode}]', 'TestsController@returnHttpStatusCode');
});
