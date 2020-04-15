<?php

namespace Abs\EmployeePkg;
use Abs\EmployeePkg\Designation;
use Abs\UserPkg\Mail\UserInvitationMail;
use App\Role;
use App\ActivityLog;
use App\Employee;
use App\Attachment;
use App\Http\Controllers\Controller;
use App\User;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use File;
use Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Validator;
use Yajra\Datatables\Datatables;

class EmployeeController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getEmployeeList(Request $request) {
		$employees = Employee::withTrashed()
			->join('users as u', 'u.entity_id', 'employees.id')
			->leftJoin('designations as d', 'd.id', 'employees.designation_id')
			->select([
				'employees.id',
				'employees.code',
				'u.first_name',
				'u.last_name',
				'u.last_name',
				'u.email',
				'u.mobile_number',
				'd.name as designation_name',
				'u.username',
				// DB::raw('COALESCE(employees.description,"--") as description'),
				DB::raw('IF(employees.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('employees.company_id', Auth::user()->company_id)
			->where('u.user_type_id', 1)

			->where(function ($query) use ($request) {
				if (!empty($request->code)) {
					$query->where('employees.code', 'LIKE', '%' . $request->code . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->first_name)) {
					$query->where('u.first_name', 'LIKE', '%' . $request->first_name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->last_name)) {
					$query->where('u.last_name', 'LIKE', '%' . $request->last_name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->mobile_number)) {
					$query->where('u.mobile_number', 'LIKE', '%' . $request->mobile_number . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->user_name)) {
					$query->where('u.username', 'LIKE', '%' . $request->user_name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->designation_id)) {
					$query->where('employees.designation_id', $request->designation_id);
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('employees.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('employees.deleted_at');
				}
			})
		;

		return Datatables::of($employees)
			->addColumn('code', function ($employee) {
				$status = $employee->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $employee->code;
			})
			->addColumn('action', function ($employee) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('edit-employee')) {
					$output .= '<a href="#!/employee-pkg/employee/edit/' . $employee->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				}
				if (Entrust::can('delete-employee')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#employee-delete-modal" onclick="angular.element(this).scope().deleteEmployee(' . $employee->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getEmployeeFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$employee = new Employee;
			$action = 'Add';
			$employee->password_change = 'Yes';
			//$employee->invitation_sent = 'No';
		} else {

			$employee = Employee::withTrashed()->with('user', 'user.profileImage')->find($id);
			$action = 'Edit';
			$employee->password_change = 'No';
			$employee->roles = $employee->user->roles()->pluck('role_id')->toArray();

			//issue / saravanan / laravel feature not used
			// $this->data['employee_attachment'] = Attachment::select('name')
			// 	->where('attachment_of_id', 120) //ATTACHMENT OF EMPLOYEE
			// 	->where('attachment_type_id', 140) //ATTACHMENT TYPE OF EMPLOYEE
			// 	->where('entity_id', $employee->user->id)
			// 	->first();
		}
		$this->data['success'] = true;
		$this->data['employee'] = $employee;
		$this->data['designation_list'] =collect(Designation::select('name', 'id')->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '', 'name' => 'Select Designation']);
		$this->data['role_list'] = collect(Role::getList());

		$this->data['action'] = $action;
		return response()->json($this->data);
	}
	public function getEmployeeFilterData() {
		$this->data['designation_list'] = collect(Designation::select('name', 'id')->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '', 'name' => 'Select Designation']);
		$this->data['success'] = true;
		return response()->json($this->data);
	}

	public function saveEmployee(Request $request) {
		//dd($request->all());
		try {
			$error_messages = [
				'code.required' => 'Code is Required',
				'code.unique' => 'Code is already taken',
				'code.min' => 'Code is Minimum 3 Charachers',
				'code.max' => 'Code is Maximum 64 Charachers',
				'first_name.max' => 'Description is Maximum 255 Charachers',
				'personal_email.unique' => 'Personal Email is already taken',
				'alternate_mobile_number.max' => 'Alternate Mobile Number is Maximum 10 Charachers',
				'github_username.max' => 'Github Username is Maximum 64 Charachers',
			];
			$validator = Validator::make($request->all(), [
				'code' => [
					'required:true',
					'min:3',
					'max:64',
					'unique:employees,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'personal_email' => [
					'nullable',
					'min:3',
					'max:64',
					'unique:employees,personal_email,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'alternate_mobile_number' => 'nullable|max:10',
				'github_username' => 'nullable|max:64',
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}
			$user_error_messages = [
				'first_name.required' => 'First Name is Required',
				'first_name.min' => 'First Name is Minimum 3 Charachers',
				'first_name.max' => 'First Name is Maximum 32 Charachers',
				//'last_name.required' => 'Last Name is Required',
				'last_name.min' => 'Last Name is Minimum 1 Charachers',
				'last_name.max' => 'Last Name is Maximum 32 Charachers',
				'email.required' => 'Email is Required',
				'email.min' => 'Email is Minimum 3 Charachers',
				'email.max' => 'Email is Maximum 191 Charachers',
				'email.unique' => 'Official Email is already taken',
				'mobile_number.required' => 'Mobile Number is Required',
				'mobile_number.min' => 'Mobile Number is Minimum 10 Charachers',
				'mobile_number.max' => 'Mobile Number is Maximum 10 Charachers',
				'mobile_number.unique' => 'Mobile Number is already taken',
				'username.required' => 'Username Number is Required',
				'username.min' => 'Username is Minimum 3 Charachers',
				'username.max' => 'Username is Maximum 32 Charachers',
				'username.unique' => 'Username is already taken',
			];
			$user_validator = Validator::make($request->user, [
				'first_name' => [
					'required:true',
					'min:3',
					'max:32',
				],
				'last_name' => [
					'nullable',
					'min:1',
					'max:32',
				],
				'email' => [
					'nullable',
					'min:3',
					'max:191',
					'unique:users,email,' . $request->id . ',entity_id',
				],
				'mobile_number' => [
					'nullable',
					'min:10',
					'max:10',
					'unique:users,mobile_number,' . $request->id . ',entity_id',
				],
				'username' => [
					'required:true',
					'min:3',
					'max:32',
					'unique:users,username,' . $request->id . ',entity_id',
				],
			], $user_error_messages);
			if ($user_validator->fails()) {
				return response()->json(['success' => false, 'errors' => $user_validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$employee = new Employee;
				$employee->company_id = Auth::user()->company_id;
				$user = new User;
				$user->company_id = Auth::user()->company_id;
				$user->created_by_id = Auth::user()->id;
			} else {
				$employee = Employee::withTrashed()->find($request->id);
				$user = User::withTrashed()->where([
					'entity_id' => $request->id,
					'user_type_id' => 1,
				])
					->first();
				$user->updated_by_id = Auth::user()->id;
			}
			$employee->fill($request->all());
			//dd($user);
			if ($request->status == 'Inactive') {
				$employee->deleted_at = Carbon::now();
				$user->deleted_by_id = Auth::user()->id;
			} else {
				$user->deleted_by_id = NULL;
				$employee->deleted_at = NULL;
			}
			$employee->save();
			if ($request->user['invitation_sent'] == 'No') {
				$user->invitation_sent=0;
			} else {
				$user->invitation_sent=1;
			}
			//dd($request->user['invitation_sent']);
			$user->fill($request->user);
			$user->has_mobile_login = 0;
			$user->entity_id = $employee->id;
			$user->user_type_id = 1;
			$user->save();
			//USER ROLE SYNC
			$user->roles()->sync(json_decode($request->roles));
			//Employee Profile Attachment
			$user_images_des = storage_path('app/public/user-profile-images/');
			//dump($user_images_des);
			Storage::makeDirectory($user_images_des, 0777);
			if (!empty($request['attachment'])) {
				if (!File::exists($user_images_des)) {
					File::makeDirectory($user_images_des, 0777, true);
				}
				$remove_previous_attachment = Attachment::where([
					'entity_id' => $user->id,
					'attachment_of_id' => 120,
					'attachment_type_id' => 140,
				])->first();
				if (!empty($remove_previous_attachment)) {
					$img_path = $user_images_des . $remove_previous_attachment->name;
					if (File::exists($img_path)) {
						File::delete($img_path);
					}
					$remove = $remove_previous_attachment->forceDelete();
				}
				$extension = $request['attachment']->getClientOriginalExtension();
				$request['attachment']->move(storage_path('app/public/user-profile-images/'), $user->id . '.' . $extension);
				$user_attachement = new Attachment;
				$user_attachement->company_id = Auth::user()->company_id;
				$user_attachement->attachment_of_id = 120; //ATTACHMENT OF EMPLOYEE
				$user_attachement->attachment_type_id = 140; //ATTACHMENT TYPE  EMPLOYEE
				$user_attachement->entity_id = $user->id;
				$user_attachement->name = $user->id . '.' . $extension;
				$user_attachement->save();
				$user->profile_image_id = $user_attachement->id;
				$user->save();

			}
			if($user->invitation_sent==1){//TO SEND USER INVIATATION
				if($user->email){
					$mail = $this->send_invitation_mail($user);
				}
				if(!empty($user->slack_api_url)){
					$data['subject'] = 'User Invitation Sent';
					$data['send_to'] = $user->slack_api_url;
					$data['action_from'] = "UserInvite";
					$data['url'] = url('/login');
					$user->notify(new \App\Notifications\Slack($data));
				}
			}

			// $activity = new ActivityLog;
			// $activity->date_time = Carbon::now();
			// $activity->user_id = Auth::user()->id;
			// $activity->module = 'Employees';
			// $activity->entity_id = $employee->id;
			// $activity->entity_type_id = 1420;
			// $activity->activity_id = $request->id == NULL ? 280 : 281;
			// $activity->activity = $request->id == NULL ? 280 : 281;
			// $activity->details = json_encode($activity);
			// $activity->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Employee Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Employee Updated Successfully',
				]);
			}
		} catch (Exceprion $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'error' => $e->getMessage(),
			]);
		}
	}
	function send_invitation_mail($user) {
		$arr['user_id'] = $user->id;
		$arr['user_name'] = $user->username;
		$arr['subject'] = 'User invitation mail for PMS web portal';
		$arr['to_email'] = $user->email;
		//dd($arr);
		$MailInstance = new UserInvitationMail($arr);
		return Mail::send($MailInstance);
	}

	public function deleteEmployee(Request $request) {
		DB::beginTransaction();
		try {
			$employee = Employee::withTrashed()->where('id', $request->id)->first();
			if ($employee) {
				$user = User::withTrashed()->where([
					'entity_id' => $request->id,
					'user_type_id' => 1,
				])
					->forceDelete();
				$employee = Employee::withTrashed()->where('id', $request->id)->forceDelete();
				/*$activity = new ActivityLog;
					$activity->date_time = Carbon::now();
					$activity->user_id = Auth::user()->id;
					$activity->module = 'Employees';
					$activity->entity_id = $request->id;
					$activity->entity_type_id = 1420;
					$activity->activity_id = 282;
					$activity->activity = 282;
					$activity->details = json_encode($activity);
				*/

				DB::commit();
				return response()->json(['success' => true, 'message' => 'Employee Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
}
