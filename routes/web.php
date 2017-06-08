<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get("sample",function(){
	
	return "TEST";
});

Route::group(['middleware' =>'cors'], function () {
    Route::post('register_user', 'APIController@register');
	Route::post('login_user', 'APIController@login');
	
	Route::group(['middleware' => 'jwt-auth'], function () {
		Route::post('get_user_details', 'APIController@get_user_details');
		Route::get('ping', "APIController@ping");
		
	});
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
