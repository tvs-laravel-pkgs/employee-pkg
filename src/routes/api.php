<?php
Route::group(['namespace' => 'Abs\EmployeePkg\Api', 'middleware' => ['api', 'auth:api']], function () {
	Route::group(['prefix' => 'api/employee-pkg'], function () {
		Route::post('punch-in/save', 'PunchController@savePunchIn')->middleware('auth:api');
		Route::post('punch/status', 'PunchController@status');
		Route::post('punch-out/save', 'PunchController@savePunchOut')->middleware('auth:api');
	});
});