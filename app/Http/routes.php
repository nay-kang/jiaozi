<?php

/*
 * |--------------------------------------------------------------------------
 * | Application Routes
 * |--------------------------------------------------------------------------
 * |
 * | Here is where you can register all of the routes for an application.
 * | It is a breeze. Simply tell Lumen the URIs it should respond to
 * | and give it the Closure to call when that URI is requested.
 * |
 */
$app->get('/blank', function () {
    return 'hello world';
});

$app->get('/pv_img.gif', 'CollectionController@pageview');
$app->get('/ec_img.gif', 'CollectionController@ecommerce');
$app->get('/event_img.gif', 'CollectionController@event');