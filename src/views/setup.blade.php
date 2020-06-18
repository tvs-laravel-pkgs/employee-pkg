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
	    when('/employee-pkg/employee/card-list', {
	        template: '<employee-card-list></employee-card-list>',
	        title: 'Employees Card List',
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
	    }).

	    //SKILL LEVELS
	    when('/employee-pkg/skill-level/list', {
	        template: '<skill-level-list></skill-level-list>',
	        title: 'Skill Levels',
	    }).
	    when('/employee-pkg/skill-level/add', {
	        template: '<skill-level-form></skill-level-form>',
	        title: 'Add Skill Level',
	    }).
	    when('/employee-pkg/skill-level/edit/:id', {
	        template: '<skill-level-form></skill-level-form>',
	        title: 'Edit Skill Level',
	    }).

	    //ATTENDANCE CHECK OUT METHODS
	    when('/employee-pkg/attendance-check-out-method/list', {
	        template: '<attendance-check-out-method-list></attendance-check-out-method-list>',
	        title: 'Attendance Check Out Methods',
	    }).
	    when('/employee-pkg/attendance-check-out-method/add', {
	        template: '<attendance-check-out-method-form></attendance-check-out-method-form>',
	        title: 'Add Attendance Check Out Method',
	    }).
	    when('/employee-pkg/attendance-check-out-method/edit/:id', {
	        template: '<attendance-check-out-method-form></attendance-check-out-method-form>',
	        title: 'Edit Attendance Check Out Method',
	    });
	}]);

	//EMPLOYEES
    var employee_list_template_url = "{{asset($employee_pkg_prefix.'/public/themes/'.$theme.'/employee-pkg/employee/list.html')}}";
    var employee_form_template_url = "{{asset($employee_pkg_prefix.'/public/themes/'.$theme.'/employee-pkg/employee/form.html')}}";
    var user_attchment_url = "{{asset('/storage/app/public/user-profile-images')}}";
 	var user_invite_url = "{{url('employee-pkg/employee/user-invitation-send')}}";

    var employee_card_list_template_url = "{{asset($employee_pkg_prefix.'/public/themes/'.$theme.'/employee-pkg/employee/card-list.html')}}";
    var employee_modal_form_template_url = "{{asset($employee_pkg_prefix.'/public/themes/'.$theme.'/employee-pkg/partials/employee-modal-form.html')}}";

	//DESIGNATION
    var designation_list_template_url = "{{asset($employee_pkg_prefix.'/public/themes/'.$theme.'/employee-pkg/designation/list.html')}}";
    var designation_form_template_url = "{{asset($employee_pkg_prefix.'/public/themes/'.$theme.'/employee-pkg/designation/form.html')}}";
    var designation_card_view_template_url = "{{asset($employee_pkg_prefix.'/public/themes/'.$theme.'/employee-pkg/designation/card-view.html')}}";
    var designation_modal_form_template_url = "{{asset($employee_pkg_prefix.'/public/themes/'.$theme.'/employee-pkg/partials/designation-modal-form.html')}}";

    //SKILL LEVELS
    var skill_level_list_template_url = "{{asset($employee_pkg_prefix.'/public/themes/'.$theme.'/employee-pkg/skill-level/list.html')}}";
    var skill_level_form_template_url = "{{asset($employee_pkg_prefix.'/public/themes/'.$theme.'/employee-pkg/skill-level/form.html')}}";

    //ATTENDANCE CHECK OUT METHODS
    var attendance_check_out_method_list_template_url = "{{asset($employee_pkg_prefix.'/public/themes/'.$theme.'/employee-pkg/attendance-check-out-method/list.html')}}";
    var attendance_check_out_method_form_template_url = "{{asset($employee_pkg_prefix.'/public/themes/'.$theme.'/employee-pkg/attendance-check-out-method/form.html')}}";

</script>
<script type="text/javascript" src="{{asset($employee_pkg_prefix.'/public/themes/'.$theme.'/employee-pkg/employee/controller.js')}}"></script>
<script type="text/javascript" src="{{asset($employee_pkg_prefix.'/public/themes/'.$theme.'/employee-pkg/designation/controller.js')}}"></script>
<script type="text/javascript" src="{{asset($employee_pkg_prefix.'/public/themes/'.$theme.'/employee-pkg/skill-level/controller.js')}}"></script>
<script type="text/javascript" src="{{asset($employee_pkg_prefix.'/public/themes/'.$theme.'/employee-pkg/attendance-check-out-method/controller.js')}}"></script>