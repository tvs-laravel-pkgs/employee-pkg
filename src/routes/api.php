<?php
Route::group(['namespace' => 'Abs\EmployeePkg\Api', 'middleware' => ['api', 'auth:api']], function () {
	Route::group(['prefix' => 'api/employee-pkg'], function () {
		Route::post('punch', 'PunchController@punch')->middleware('auth:api');
		Route::post('punch/status', 'PunchController@status');
	});
});