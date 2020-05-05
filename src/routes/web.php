<?php

Route::group(['namespace' => 'Abs\EmployeePkg', 'middleware' => ['web', 'auth'], 'prefix' => 'employee-pkg'], function () {

	//EMPLOYEES
	Route::get('/employee/get-list', 'EmployeeController@getEmployeeList')->name('getEmployeeList');
	Route::get('/employee/get-form-data', 'EmployeeController@getEmployeeFormData')->name('getEmployeeFormData');
	Route::post('/employee/save', 'EmployeeController@saveEmployee')->name('saveEmployee');
	Route::get('/employee/delete', 'EmployeeController@deleteEmployee')->name('deleteEmployee');
	Route::get('/employee/get-filter-data', 'EmployeeController@getEmployeeFilterData')->name('getEmployeeFilterData');
	Route::get('/employee/card-view', 'EmployeeController@getEmployees')->name('getEmployees');
	Route::get('/employee/user-invitation-send/{id}', 'EmployeeController@sendUserInvitationMail')->name('sendUserInvitationMail');

	//DESIGNATION
	Route::get('/designation/get-list', 'DesignationController@getDesignationList')->name('getDesignationList');
	Route::get('/designation/get-form-data', 'DesignationController@getDesignationFormData')->name('getDesignationFormData');
	Route::post('/designation/save', 'DesignationController@saveDesignation')->name('saveDesignation');
	Route::get('/designation/delete', 'DesignationController@deleteDesignation')->name('deleteDesignation');
	Route::get('/designation/card-view', 'DesignationController@getDesignations')->name('getDesignations');

	//SKILL LEVELS 
	Route::get('/skill-level/get-list', 'SkillLevelController@getSkillLevelList')->name('getSkillLevelList');
	Route::get('/skill-level/get-form-data', 'SkillLevelController@getSkillLevelFormData')->name('getSkillLevelFormData');
	Route::post('/skill-level/save', 'SkillLevelController@saveSkillLevel')->name('saveSkillLevel');
	Route::get('/skill-level/delete', 'SkillLevelController@deleteSkillLevel')->name('deleteSkillLevel');
	Route::get('/skill-level/get-filter-data', 'SkillLevelController@getSkillLevelFilter')->name('getSkillLevelFilter');

	//PUNCH OUT METHODS 
	Route::get('/punch-out-method/get-list', 'PunchOutMethodController@getPunchOutMethodList')->name('getPunchOutMethodList');
	Route::get('/punch-out-method/get-form-data', 'PunchOutMethodController@getPunchOutMethodFormData')->name('getPunchOutMethodFormData');
	Route::post('/punch-out-method/save', 'PunchOutMethodController@savePunchOutMethod')->name('savePunchOutMethod');
	Route::get('/punch-out-method/delete', 'PunchOutMethodController@deletePunchOutMethod')->name('deletePunchOutMethod');
	Route::get('/punch-out-method/get-filter-data', 'PunchOutMethodController@getPunchOutMethodFilter')->name('getPunchOutMethodFilter');

});