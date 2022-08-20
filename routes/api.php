<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('user/signup', 'App\Http\Controllers\API\AccountController@signup');
Route::post('common/add-comment', 'App\Http\Controllers\API\CommonController@addComment');
Route::get('common/check-traveling', 'App\Http\Controllers\API\CommonController@checkTraveling');
Route::post('traveler/start-travel', 'App\Http\Controllers\API\TravelerController@startTravel');
