<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// APIのルーティングを設定
Route::post('common/add-comment', 'App\Http\Controllers\API\CommonController@addComment');
Route::get('common/check-traveling', 'App\Http\Controllers\API\CommonController@checkTraveling');
Route::get('common/get-info', 'App\Http\Controllers\API\CommonController@getInfo');
Route::post('common/save-location', 'App\Http\Controllers\API\CommonController@saveLocation');
Route::post('common/update-info', 'App\Http\Controllers\API\CommonController@updateInfo');
Route::post('traveler/add-report', 'App\Http\Controllers\API\TravelerController@addReport');
Route::post('traveler/finish-travel', 'App\Http\Controllers\API\TravelerController@finishTravel');
Route::post('traveler/start-travel', 'App\Http\Controllers\API\TravelerController@startTravel');
Route::post('user/signup', 'App\Http\Controllers\API\AccountController@signup');
