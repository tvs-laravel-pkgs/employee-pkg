<?php

namespace Abs\EmployeePkg;
use Abs\EmployeePkg\Employee;
use App\ActivityLog;
use App\Http\Controllers\Controller;
use App\User;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
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
				if (!empty($request->name)) {
					$query->where('employees.name', 'LIKE', '%' . $request->name . '%');
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
		} else {
			$employee = Employee::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['employee'] = $employee;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function saveEmployee(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'code.required' => 'Name is Required',
				'code.unique' => 'Name is already taken',
				'code.min' => 'Name is Minimum 3 Charachers',
				'code.max' => 'Name is Maximum 64 Charachers',
				'first_name.max' => 'Description is Maximum 255 Charachers',
			];
			$validator = Validator::make($request->all(), [
				'code' => [
					'required:true',
					'min:3',
					'max:64',
					'unique:employees,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'first_name' => 'nullable|max:255',
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
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
				]);
				$user->updated_by_id = Auth::user()->id;
			}
			$employee->fill($request->all());
			if ($request->status == 'Inactive') {
				$employee->deleted_at = Carbon::now();
				$user->deleted_by_id = Auth::user()->id;
			} else {
				$user->deleted_by_id = NULL;
				$employee->deleted_at = NULL;
			}
			$employee->save();

			$user->fill($request->user);
			$user->has_mobile_login = 0;
			$user->entity_id = $employee->id;
			$user->user_type_id = 1;
			$user->save();

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

	public function deleteEmployee(Request $request) {
		DB::beginTransaction();
		try {
			$employee = Employee::withTrashed()->where('id', $request->id)->forceDelete();
			if ($employee) {

				$activity = new ActivityLog;
				$activity->date_time = Carbon::now();
				$activity->user_id = Auth::user()->id;
				$activity->module = 'Employees';
				$activity->entity_id = $request->id;
				$activity->entity_type_id = 1420;
				$activity->activity_id = 282;
				$activity->activity = 282;
				$activity->details = json_encode($activity);
				$activity->save();

				DB::commit();
				return response()->json(['success' => true, 'message' => 'Employee Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
}
