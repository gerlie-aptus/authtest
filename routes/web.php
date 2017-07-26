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
   //return view('welcome');
	return 'testingssssssss';
});


Route::group(['middleware' =>'cors'], function () {
    Route::post('register_user', 'APIController@register');
	Route::post('login_user', 'APIController@login');
	
	Route::post('fabio_login', 'FabioUser@fabio_login');

	Route::get("radius_update","IseekApi@update_fabio_services");	

	Route::group(['middleware' => 'jwt-auth'], function () {
	  Route::group(['middleware' => 'authorize-jwt'], function () {
		Route::post('get_user_details', 'APIController@get_user_details');
		Route::get('ping', "APIController@ping");
		

		$data=array("0001"=>"ping");

		/*Get all records of ping */
		Route::get($data['0001'],"IseekApi@index");

		/*Get all records of accounts*/
		Route::get("accounts","IseekApi@accounts");

		/*Add new record services*/
		Route::post("services","IseekApi@create_service");

		/*Get service record by id*/
    	Route::get("service/{service_id}","IseekApi@service");

    	/*Update Service record Password*/
    	Route::put("service/{service_id}/update",'IseekApi@update_service');

        /*Manage service*/
        Route::post("service/manage/{service_id}",'IseekApi@manage_service');

        /*Delete static ip*/
        Route::delete("service/{service_id}/static_ip",'IseekApi@service_delete_staticip');

        /*Delete service garden*/
        Route::delete("service/{service_id}/garden","IseekApi@service_delete_garden");

        /*Delete service shape*/
        Route::delete("service/{service_id}/shape",'IseekApi@service_delete_shape');

        Route::get('test',"IseekApi@test");
	
	Route::get("radius_service_id/{radius_service_id}","IseekApi@get_user_services_id");

	});
     });

});

//Route::get("radius_service_id/{radius_service_id}","IseekApi@get_user_services_id");

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
