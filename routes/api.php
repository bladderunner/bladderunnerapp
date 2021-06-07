<?php

use Illuminate\Http\Request;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => [\Barryvdh\Cors\HandleCors::class]], function()
{
	Route::post('registration', 'UserController@registration');
	Route::post('login', 'UserController@login');
	Route::post('testPush', 'UserController@testPush');
	Route::post('forgotPassword', 'UserController@forgotPassword');
	Route::post('resetPassword', 'UserController@resetPassword');
	Route::post('resetPin', 'UserController@resetPin');
        Route::post('confirmPin/{id}', 'UserController@confirmPin');
	Route::post('viewedTnC', 'UserController@viewedTnC');
	Route::post('encryptExistingRegistrations', 'UserController@encryptExistingRegistrations');
        Route::get('aboutUs', 'UserController@aboutUs');    
});
	
Route::group(['middleware' => [\Barryvdh\Cors\HandleCors::class,'jwt.auth']], function(){

	Route::post('leaderBoardMonthly', 'UserController@leaderBoardMonthly');
	Route::post('leaderBoardWeekly', 'UserController@leaderBoardWeekly');
    Route::post('leaderBoardDaily', 'UserController@leaderBoardDaily');
    Route::post('profileUpdate', 'UserController@profileUpdate');
    Route::get('profile/{id}', 'UserController@profile');
    Route::post('symptomSave', 'UserController@symptomSave');
    Route::post('symptomData', 'UserController@symptomData');
    Route::post('medicationSave', 'UserController@medicationSave');
    Route::post('medicationData', 'UserController@medicationData');
    Route::post('dietSave', 'UserController@dietSave');
    Route::post('dietData', 'UserController@dietData');
    Route::post('moodSave', 'UserController@moodSave');
    Route::post('moodData', 'UserController@moodData');
    Route::post('activitySave', 'UserController@activitySave');
    Route::post('activityData', 'UserController@activityData');
    Route::post('logout', 'UserController@logout');
    Route::post('updateDeviceToken', 'UserController@updateDeviceToken');    
    
    Route::post('users', 'AdminController@users');
    Route::post('monthlyReport', 'AdminController@monthlyReport');
    Route::post('profileDelete', 'AdminController@profileDelete');
    Route::post('profileUpdateAdmin', 'AdminController@profileUpdate');
});
