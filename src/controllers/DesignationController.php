<?php

namespace Abs\EmployeePkg;
use Abs\EmployeePkg\Designation;
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

class DesignationController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getDesignationList(Request $request) {
		$designations = Designation::withTrashed()
			
			->select([
				'designations.id',
				'designations.name',
				'designations.short_name',
				
				DB::raw('IF(designations.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('designations.company_id', Auth::user()->company_id)

			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('designations.name', 'LIKE', '%' . $request->name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('designations.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('designations.deleted_at');
				}
			})
		;

		return Datatables::of($designations)
			->rawColumns(['name','action'])
			->addColumn('name', function ($designation) {
				$status = $designation->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $designation->name;
			})
			->addColumn('action', function ($designation) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('edit-designation')) {
					$output .= '<a href="#!/employee-pkg/designation/edit/' . $designation->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				}
				if (Entrust::can('delete-designation')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#designation-delete-modal" onclick="angular.element(this).scope().deleteDesignation(' . $designation->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getDesignationFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$designation = new Designation;
			$action = 'Add';
		} else {
			$designation = Designation::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['designation'] = $designation;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function saveDesignation(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'short_name.required' => 'Short Name is Required',
				'short_name.unique' => 'Short Name is already taken',
				'short_name.min' => 'Short Name is Minimum 3 Charachers',
				'short_name.max' => 'Short Name is Maximum 32 Charachers',
				'name.required' => 'Name is Required',
				'name.unique' => 'Name is already taken',
				'name.min' => 'Name is Minimum 3 Charachers',
				'name.max' => 'Name is Maximum 191 Charachers',
			];
			$validator = Validator::make($request->all(), [
				'short_name' => [
					'required:true',
					'min:3',
					'max:32',
					'unique:designations,short_name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => [
					'required:true',
					'min:3',
					'max:191',
					'unique:designations,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$designation = new Designation;
				$designation->company_id = Auth::user()->company_id;
				// $user = new User;
				// $user->company_id = Auth::user()->company_id;
				// $user->created_by_id = Auth::user()->id;
			} else {
				$designation = Designation::withTrashed()->find($request->id);
				// $user = User::withTrashed()->where([
				// 	'entity_id' => $request->id,
				// 	'user_type_id' => 1,
				// ]);
				// $user->updated_by_id = Auth::user()->id;
			}
			$designation->fill($request->all());
			if ($request->status == 'Inactive') {
				$designation->deleted_at = Carbon::now();
				// $user->deleted_by_id = Auth::user()->id;
			} else {
				// $user->deleted_by_id = NULL;
				$designation->deleted_at = NULL;
			}
			$designation->save();

			// $user->fill($request->user);
			// $user->has_mobile_login = 0;
			// $user->entity_id = $designation->id;
			// $user->user_type_id = 1;
			// $user->save();

			// $activity = new ActivityLog;
			// $activity->date_time = Carbon::now();
			// $activity->user_id = Auth::user()->id;
			// $activity->module = 'Designations';
			// $activity->entity_id = $designation->id;
			// $activity->entity_type_id = 1420;
			// $activity->activity_id = $request->id == NULL ? 280 : 281;
			// $activity->activity = $request->id == NULL ? 280 : 281;
			// $activity->details = json_encode($activity);
			// $activity->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Designation Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Designation Updated Successfully',
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

	public function deleteDesignation(Request $request) {
		DB::beginTransaction();
		// dd($request->id);
		try {
			$designation = Designation::withTrashed()->where('id', $request->id)->forceDelete();
			if ($designation) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Designation Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function getDesignations(Request $request) {
		$designations = Designation::withTrashed()
			->with([
				'employees',
				'employees.user'
			])
			->select([
				'designations.id',
				'designations.name',
				'designations.short_name',
				DB::raw('IF(designations.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('designations.company_id', Auth::user()->company_id)
			->get();

		return response()->json([
			'success' => true,
			'designations' => $designations,
		]);		
	}
}