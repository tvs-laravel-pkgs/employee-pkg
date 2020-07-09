<?php

namespace Abs\EmployeePkg\Api;

use Abs\EmployeePkg\PunchOutMethod;
use App\AttendanceLog;
use App\Employee;
use App\Http\Controllers\Controller;
use App\User;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Validator;

class PunchController extends Controller {
	public $successStatus = 200;

	public function punch(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'encrypted_id' => [
					'required',
					'numeric',
					'exists:users,id',
				],
			]);
			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'message' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				], $this->successStatus);
			}

			$user = User::find($request->encrypted_id);
			if (!$user) {
				return response()->json([
					'status' => false,
					'message' => 'User not found',
				], $this->successStatus);
			}

			DB::beginTransaction();

			$punch = AttendanceLog::whereDate('date', date('Y-m-d'))
				->where('user_id', $user->id)
				->whereNull('out_time')
				->orderBy('id', 'DESC')
				->first();

			if ($punch) {
				//Already Punch In
				//PUNCH OUT
				$punch_in_time = (date('h:i:s A', strtotime('+1 minutes', strtotime($punch->in_time))));
				$current_time = date('h:i:s A');

				$punch_in = strtotime($punch_in_time);
				$current_in = strtotime($current_time);

				if ($current_in < $punch_in) {
					$action = "In";
				} else {
					$action = "Out";
					$data['punch_data'] = $punch;
					$data['punch_out_method_list'] = PunchOutMethod::getList();
				}
			} else {
				//PUNCH IN
				$punch = new AttendanceLog();
				$punch->user_id = $user->id;
				$punch->date = $date = date('Y-m-d');
				$punch->in_time = date('H:i:s');
				$punch->punch_in_outlet_id = Auth::user()->working_outlet_id;
				$punch->created_by_id = Auth::id();
				$punch->save();
				$action = "In";

				//UPDATE OUTLET DETAIL TO EMPLOYEE TABLE
				$auth_user_of_employee = Employee::find(Auth::user()->entity_id);
				$auth_user_outlet_id = $auth_user_of_employee->outlet_id;

				if ($auth_user_outlet_id != $user->employee->outlet_id) {
					$update_employee = Employee::where('id', $user->employee->id)
						->update([
							'deputed_outlet_id' => $auth_user_outlet_id,
							'updated_at' => Carbon::now(),
						]);
				}
			}

			$data['action'] = $action;

			$user->employee->outlet;
			$user->role;
			$user->punch_data = $punch;

			$data['punch_user'] = $user;

			DB::commit();

			if ($action == 'In') {
				return response()->json([
					'success' => true,
					'message' => 'Punch ' . $action . ' success!',
					'data' => $data,
				], $this->successStatus);
			} else {
				return response()->json([
					'success' => true,
					'data' => $data,
				], $this->successStatus);
			}

		} catch (Exception $e) {
			return response()->json([
				'status' => false,
				'message' => 'Punch in failed!',
			], $this->successStatus);
		}
	}

	public function savePunchOut(Request $request) {
		// dd($request->all());
		try {

			$validator = Validator::make($request->all(), [
				'encrypted_id' => [
					'required',
					'numeric',
					'exists:users,id',
				],
				'punch_out_method_id' => [
					'required',
					'numeric',
					'exists:punch_out_methods,id',
				],
				'remarks' => [
					'nullable',
					'string',
					'max:255',
				],
			]);
			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'errors' => $validator->errors()->all(),
				], $this->successStatus);
			}
			$user = User::find($request->encrypted_id);
			if (!$user) {
				return response()->json([
					'success' => false,
					'message' => 'User not found',
				], $this->successStatus);
			}

			$punch = AttendanceLog::whereDate('date', date('Y-m-d'))->where('user_id', $user->id)->whereNull('out_time')->first();
			if (!$punch) {
				return response()->json([
					'success' => false,
					'message' => 'Punch in details not found',
				], $this->successStatus);
			}
			DB::beginTransaction();

			$time1 = strtotime($punch->in_time);
			$punch_out_time = date('H:i:s');

			$time2 = strtotime($punch_out_time);
			if ($time2 < $time1) {
				$time2 += 86400;
			}
			$difference = date("H:i", strtotime("00:00") + ($time2 - $time1));
			$punch->out_time = $punch_out_time;
			$punch->duration = $difference;
			$punch->punch_out_method_id = $request->punch_out_method_id;
			$punch->remarks = $request->remarks;
			$punch->updated_by_id = Auth::id();
			$punch->save();

			$user->employee->outlet;
			$user->role;
			$user->punch_data = $punch;

			$data['punch_user'] = $user;

			//UPDATE OUTLET DETAIL TO EMPLOYEE TABLE
			$auth_user_of_employee = Employee::find(Auth::user()->entity_id);
			$auth_user_outlet_id = $auth_user_of_employee->outlet_id;

			if ($auth_user_outlet_id != $user->employee->outlet_id) {
				$update_employee = Employee::where('id', $user->employee->id)
					->update([
						'deputed_outlet_id' => NULL,
						'updated_at' => Carbon::now(),
					]);
			}

			DB::commit();
			return response()->json([
				'success' => true,
				'message' => 'Punch Out success!',
				'data' => $data,
			], $this->successStatus);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'message' => 'Punch  Out failed!',
			], $this->successStatus);
		}

	}
}
