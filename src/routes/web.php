<?php

Route::group(['namespace' => 'Abs\EmployeePkg', 'middleware' => ['web', 'auth'], 'prefix' => 'employee-pkg'], function () {

	//EMPLOYEES
	Route::get('/employee/get-list', 'EmployeeController@getEmployeeList')->name('getEmployeeList');
	Route::get('/employee/get-form-data', 'EmployeeController@getEmployeeFormData')->name('getEmployeeFormData');
	Route::post('/employee/save', 'EmployeeController@saveEmployee')->name('saveEmployee');
	Route::get('/employee/delete', 'EmployeeController@deleteEmployee')->name('deleteEmployee');

	//DESIGNATION
	Route::get('/designation/get-list', 'DesignationController@getDesignationList')->name('getDesignationList');
	Route::get('/designation/get-form-data', 'DesignationController@getDesignationFormData')->name('getDesignationFormData');
	Route::post('/designation/save', 'DesignationController@saveDesignation')->name('saveDesignation');
	Route::get('/designation/delete', 'DesignationController@deleteDesignation')->name('deleteDesignation');

});