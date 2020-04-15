@if(config('employee-pkg.DEV'))
    <?php $employee_pkg_prefix = '/packages/abs/employee-pkg/src';?>
@else
    <?php $employee_pkg_prefix = '';?>
@endif

<script type="text/javascript">
	app.config(['$routeProvider', function($routeProvider) {

	    $routeProvider.
	    //EMPLOYEE
	    when('/employee-pkg/employee/list', {
	        template: '<employee-list></employee-list>',
	        title: 'Employees',
	    }).
	    when('/employee-pkg/employee/add', {
	        template: '<employee-form></employee-form>',
	        title: 'Add Employee',
	    }).
	    when('/employee-pkg/employee/edit/:id', {
	        template: '<employee-form></employee-form>',
	        title: 'Edit Employee',
	    }).

	    //DESIGNATION
	    when('/employee-pkg/designation/list', {
	        template: '<designation-list></designation-list>',
	        title: 'Designations',
	    }).
	    when('/employee-pkg/designation/add', {
	        template: '<designation-form></designation-form>',
	        title: 'Add Designation',
	    }).
	    when('/employee-pkg/designation/edit/:id', {
	        template: '<designation-form></designation-form>',
	        title: 'Edit Designation',
	    }).
	    when('/employee-pkg/designation/card-view', {
	        template: '<designation-card-view></designation-card-view>',
	        title: 'Designations Card View',
	    });

	}]);

	//EMPLOYEES
    var employee_list_template_url = "{{asset($employee_pkg_prefix.'/public/themes/'.$theme.'/employee-pkg/employee/list.html')}}";
    var employee_form_template_url = "{{asset($employee_pkg_prefix.'/public/themes/'.$theme.'/employee-pkg/employee/form.html')}}";
    var user_attchment_url = "{{asset('/storage/app/public/user-profile-images')}}";
	//DESIGNATION
    var designation_list_template_url = "{{asset($employee_pkg_prefix.'/public/themes/'.$theme.'/employee-pkg/designation/list.html')}}";
    var designation_form_template_url = "{{asset($employee_pkg_prefix.'/public/themes/'.$theme.'/employee-pkg/designation/form.html')}}";
    var designation_card_view_template_url = "{{asset($employee_pkg_prefix.'/public/themes/'.$theme.'/employee-pkg/designation/card-view.html')}}";
    var designation_modal_form_template_url = "{{asset($employee_pkg_prefix.'/public/themes/'.$theme.'/employee-pkg/partials/designation-modal-form.html')}}";

</script>
<script type="text/javascript" src="{{asset($employee_pkg_prefix.'/public/themes/'.$theme.'/employee-pkg/employee/controller.js')}}"></script>
<script type="text/javascript" src="{{asset($employee_pkg_prefix.'/public/themes/'.$theme.'/employee-pkg/designation/controller.js')}}"></script>
