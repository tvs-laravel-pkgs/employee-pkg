<?php

namespace Abs\EmployeePkg;
use Abs\EmployeePkg\PunchOutMethod;
use App\Http\Controllers\Controller;
use App\User;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class PunchOutMethodController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getPunchOutMethodFilter() {
		$this->data['extras'] = [
			'status' => [
				['id' => '', 'name' => 'Select Status'],
				['id' => '1', 'name' => 'Active'],
				['id' => '0', 'name' => 'Inactive'],
			],
		];
		return response()->json($this->data);
	}

	public function getPunchOutMethodList(Request $request) {
		$punch_out_methods = PunchOutMethod::withTrashed()

			->select([
				'punch_out_methods.id',
				'punch_out_methods.name',

				DB::raw('IF(punch_out_methods.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('punch_out_methods.company_id', Auth::user()->company_id)

			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('punch_out_methods.name', 'LIKE', '%' . $request->name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('punch_out_methods.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('punch_out_methods.deleted_at');
				}
			})
		;

		return Datatables::of($punch_out_methods)
		// ->rawColumns(['name', 'action'])
			->addColumn('status', function ($punch_out_method) {
				$status = $punch_out_method->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $punch_out_method->status;
			})
			->addColumn('action', function ($punch_out_method) {
				$img_edit = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img_edit_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$action = '';

				if (Entrust::can('edit-punch-out-method')) {
					$action .= '<a href="#!/employee-pkg/punch-out-method/edit/' . $punch_out_method->id . '" id = "" title="Edit"><img src="' . $img_edit . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img_edit_active . '" onmouseout=this.src="' . $img_edit . '"></a>';
				}
				if (Entrust::can('delete-punch-out-method')) {
					$action .= '<a href="javascript:;" data-toggle="modal" data-target="#delete_punch_out_method" onclick="angular.element(this).scope().deletePunchOutMethod(' . $punch_out_method->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete_active . '" onmouseout=this.src="' . $img_delete . '"></a>';
				}
				return $action;
			})
			->make(true);
	}

	public function getPunchOutMethodFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$punch_out_method = new PunchOutMethod;
			$action = 'Add';
		} else {
			$punch_out_method = PunchOutMethod::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['punch_out_method'] = $punch_out_method;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function savePunchOutMethod(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'name.required' => 'Name is Required',
				'name.unique' => 'Name is already taken',
				'name.min' => 'Name is Minimum 3 Charachers',
				'name.max' => 'Name is Maximum 64 Charachers',
			];
			$validator = Validator::make($request->all(), [
				'name' => [
					'required:true',
					'min:3',
					'max:64',
					'unique:punch_out_methods,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$punch_out_method = new PunchOutMethod;
				$punch_out_method->company_id = Auth::user()->company_id;
			} else {
				$punch_out_method = PunchOutMethod::withTrashed()->find($request->id);
			}
			$punch_out_method->fill($request->all());
			if ($request->status == 'Inactive') {
				$punch_out_method->deleted_at = Carbon::now();
			} else {
				$punch_out_method->deleted_at = NULL;
			}
			$punch_out_method->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Check Out Method Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Check Out Method Updated Successfully',
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

	public function deletePunchOutMethod(Request $request) {
		DB::beginTransaction();
		// dd($request->id);
		try {
			$punch_out_method = PunchOutMethod::withTrashed()->where('id', $request->id)->forceDelete();
			if ($punch_out_method) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Check Out Method Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

}