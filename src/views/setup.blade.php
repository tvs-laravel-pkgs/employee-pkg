@if(config('employee-pkg.DEV'))
    <?php $employee_pkg_prefix = '/packages/abs/employee-pkg/src';?>
@else
    <?php $employee_pkg_prefix = '';?>
@endif

<script type="text/javascript">

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
<!-- <script type="text/javascript" src="{{asset($employee_pkg_prefix.'/public/themes/'.$theme.'/employee-pkg/employee/controller.js')}}"></script>
<script type="text/javascript" src="{{asset($employee_pkg_prefix.'/public/themes/'.$theme.'/employee-pkg/designation/controller.js')}}"></script>
<script type="text/javascript" src="{{asset($employee_pkg_prefix.'/public/themes/'.$theme.'/employee-pkg/skill-level/controller.js')}}"></script>
<script type="text/javascript" src="{{asset($employee_pkg_prefix.'/public/themes/'.$theme.'/employee-pkg/attendance-check-out-method/controller.js')}}"></script> -->