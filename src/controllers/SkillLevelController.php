<?php

namespace Abs\EmployeePkg;

use Abs\ApprovalPkg\ApprovalType;
use Abs\ApprovalPkg\EntityStatus;
use Abs\EmployeePkg\SkillLevel;
use App\ActivityLog;
use App\Config;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class SkillLevelController extends Controller
{
    public function __construct() {
		$this->data['theme'] = config('custom.admin_theme');
	}

	public function getSkillLevelFilter() {
		$this->data['extras'] = [
			'status' => [
				['id' => '', 'name' => 'Select Status'],
				['id' => '1', 'name' => 'Active'],
				['id' => '0', 'name' => 'Inactive'],
			],
		];
		return response()->json($this->data);
	}

	public function getSkillLevelList(Request $request) {
		$skill_levels = SkillLevel::withTrashed()
			->select([
				'skill_levels.id',
				'skill_levels.short_name',
				'skill_levels.name',
				'skill_levels.description',
				DB::raw('IF(skill_levels.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where(function ($query) use ($request) {
				if (!empty($request->short_name)) {
					$query->where('skill_levels.short_name', 'LIKE', '%' . $request->short_name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('skill_levels.name', 'LIKE', '%' . $request->name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('skill_levels.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('skill_levels.deleted_at');
				}
			})
			->where('skill_levels.company_id', Auth::user()->company_id)
		;

		return Datatables::of($skill_levels)
			
			->addColumn('status', function ($skill_level) {
				$status = $skill_level->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indigator ' . $status . '"></span>' . $skill_level->status;
			})
			->addColumn('action', function ($skill_level) {
				// $img_edit = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				// $img_edit_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				// $img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				// $img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				// $output = '';
				$action = '';
				$img1 = asset('public/theme/img/table/edit.svg');
				$img1_active = asset('public/theme/img/table/edit-hover.svg');
				$img2 = asset('public/theme/img/table/view.svg');
				$img2_active = asset('public/theme/img/table/view-hover.svg');
				$img3 = asset('public/theme/img/table/delete.svg');
				$img3_active = asset('public/theme/img/table/delete-hover.svg');
				
				if (Entrust::can('edit-skill-level')) {
					// $output .= '<a href="#!/employee-pkg/skill-level/edit/' . $skill_level->id . '" id = "" title="Edit"><img src="' . $img_edit . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img_edit_active . '" onmouseout=this.src="' . $img_edit . '"></a>';
					$action .= '<a href="#!/employee-pkg/skill-level/edit/' . $skill_level->id . '" class=""><img class="img-responsive" src="' . url($img1) . '" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '" class="action"</i></a>';
				}
				if (Entrust::can('delete-skill-level')) {
					// $output .= '<a href="javascript:;" data-toggle="modal" data-target="#delete_skill_level" onclick="angular.element(this).scope().deleteSkillLevel(' . $skill_level->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete_active . '" onmouseout=this.src="' . $img_delete . '"></a>';
					$action .= '<a href="javascript:;" data-toggle="modal" data-target="#delete_skill_level" onclick="angular.element(this).scope().deleteSkillLevel(' . $skill_level->id . ')" dusk = "delete-btn" title="Delete"> <img src="' . $img3 . '" alt="delete" class="img-responsive" onmouseover="this.src="' . $img3_active . '" onmouseout="this.src="' . $img3 . '" > </a>';
				}
				// return $output;
				return $action;
			})
			->make(true);
	}

	public function getSkillLevelFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$skill_level = new SkillLevel;
			$action = 'Add';
		} else {
			$skill_level = SkillLevel::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['action'] = $action;
		$this->data['skill_level'] = $skill_level;
		return response()->json($this->data);
	}

	public function saveSkillLevel(Request $request) {
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
				'name.max' => 'Name is Maximum 128 Charachers',
				'description.min' => 'Description is Minimum 3 Charachers',
				'description.max' => 'Description is Maximum 255 Charachers',
			];
			$validator = Validator::make($request->all(), [
				'short_name' => [
					'required:true',
					'min:3',
					'max:32',
					'unique:skill_levels,short_name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => [
					'required:true',
					'min:3',
					'max:128',
					'unique:skill_levels,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'description' => [
					'min:3',
					'max:255',
				],	
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$skill_level = new SkillLevel;
				$skill_level->created_by_id = Auth::user()->id;
				$skill_level->created_at = Carbon::now();
				$skill_level->updated_at = NULL;
			} else {
				$skill_level = SkillLevel::withTrashed()->find($request->id);
				$skill_level->updated_by_id = Auth::user()->id;
				$skill_level->updated_at = Carbon::now();
			}
			$skill_level->fill($request->all());
			$skill_level->company_id = Auth::user()->company_id;
			if ($request->status == 'Inactive') {
				$skill_level->deleted_at = Carbon::now();
				$skill_level->deleted_by_id = Auth::user()->id;
			} else {
				$skill_level->deleted_by_id = NULL;
				$skill_level->deleted_at = NULL;
			}
			$skill_level->save();
			
			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Skill Level Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Skill Level Updated Successfully',
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

	public function deleteSkillLevel(Request $request) {
		DB::beginTransaction();
		try {
			$skill_level = SkillLevel::withTrashed()->where('id', $request->id)->forceDelete();
			if ($skill_level) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Skill Level Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
}