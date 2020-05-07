<?php

namespace Abs\EmployeePkg\Api;

use App\AttendanceLog;
use App\Http\Controllers\Controller;
use App\PunchOutMethod;
use App\User;
use Auth;
use DB;
use Illuminate\Http\Request;
use Validator;

class PunchController extends Controller {
	public $successStatus = 200;

	//issue : saravanan : unwanted
	// public function status(Request $request) {
	// 	try {

	// 		/*if (!Entrust::can('punch')) {
	// 			return response()->json(['success' => false, 'message' => 'Permission Denied!'], $this->successStatus);
	// 		}*/

	// 		$validator = Validator::make($request->all(), [
	// 			'encrypted_id' => [
	// 				'required',
	// 				'numeric',
	// 				'exists:users,id',
	// 			],
	// 		]);
	// 		if ($validator->fails()) {
	// 			// dd($validator->errors()->all()->toArray());
	// 			return response()->json([
	// 				'success' => false,
	// 				'message' => 'Validation Error',
	// 				'errors' => $validator->errors()->all(),
	// 			], $this->successStatus);
	// 			// issue : saravanan : readability
	// 			// return response()->json(['success' => false, 'message' => 'Validation Error', 'errors' => $validator->errors()], $this->successStatus);
	// 		}
	// 		$logged_user = User::find(Auth::user()->id);
	// 		if (!$logged_user) {
	// 			return response()->json([
	// 				'status' => false,
	// 				'message' => 'Logged user not found',
	// 			], $this->successStatus);
	// 		}
	// 		$logged_user->employee->outlet;

	// 		$user = User::find($request->encrypted_id);
	// 		if (!$user) {
	// 			// issue : saravanan : readability
	// 			return response()->json([
	// 				'status' => false,
	// 				'message' => 'Employee not found',
	// 			], $this->successStatus);
	// 			// return response()->json(['status' => false, 'message' => 'Employee not found'], $this->successStatus);
	// 		}

	// 		$punch = AttendanceLog::whereDate('date', date('Y-m-d'))->where('user_id', $user->id)->orderBy('id', 'DESC')->first();
	// 		if ($punch) {
	// 			if (!$punch->out_time) {
	// 				$action = "Out";
	// 				$data['punch_in'] = $punch;
	// 				$data['punch_out_method_list'] = PunchOutMethod::getList();
	// 			} else {
	// 				$action = "In";
	// 			}
	// 		} else {
	// 			$action = "In";
	// 		}
	// 		$data['action'] = $action;
	// 		$data['date'] = date('Y-m-d');
	// 		$data['time'] = date('h:i a');
	// 		//$user->profile_image_url = $user->profile_image_url;
	// 		$data['user'] = $user;
	// 		$data['logged_user'] = $logged_user;
	// 		//$data['punch_out_method_list']=PunchOutMethod::getList();
	// 		//dd($data);
	// 		return response()->json(['success' => true, 'data' => $data], $this->successStatus);
	// 	} catch (Exception $e) {
	// 		return response()->json(['status' => false, 'message' => 'Punch ' . $type . ' failed!'], $this->successStatus);
	// 	}
	// }

	public function punch(Request $request) {
		try {

			/*if (!Entrust::can('punch')) {
				return response()->json(['success' => false, 'message' => 'Permission Denied!'], $this->successStatus);
			}*/

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
					'errors' => $validator->errors()->all(),
				], $this->successStatus);
			}

			//$user_id = Crypt::decrypt($request->qr_code);
			$user = User::with([
				'employee',
				'employee.outlet',
				'employee.designation',
			])
				->find($request->encrypted_id);

			if (!$user) {
				return response()->json([
					'success' => false,
					'message' => 'Employee not found',
				], $this->successStatus);
				//issue : saravanan : no readability
				// return response()->json([
				// 'success' => false, 'message' => 'Employee not found'], $this->successStatus);
			}

			//issue : saravanan : scurity issue
			if ($user->company_id != Auth::user()->company_id) {
				return response()->json([
					'success' => false,
					'message' => 'Employee not belongs to logged employee',
				], $this->successStatus);
			}

			$attendance_log = AttendanceLog::whereDate('date', date('Y-m-d'))->where('user_id', $user->id)->whereNull('out_time')->first();
			//Employee already checked in so show checkout form
			if ($attendance_log) {
				return response()->json([
					'success' => true,
					'status_id' => 2,
					'attendance_log' => $attendance_log,
					'user' => $user,
					'extras' => [
						'punch_out_method_list' => PunchOutMethod::getList(),
					],
				], $this->successStatus);
			}

			DB::beginTransaction();
			$attendance_log = new AttendanceLog();
			$attendance_log->date = $date = date('Y-m-d');
			$time = date('H:i:s');
			$punch_in_time = strtotime($time) - (10 * 60);
			$attendance_log->in_time = date('H:i:s', $punch_in_time);
			$attendance_log->user_id = $user->id;
			$attendance_log->created_by_id = Auth::id();
			$attendance_log->save();
			DB::commit();
			return response()->json([
				'success' => true,
				'status_id' => 1,
				'attendance_log' => $attendance_log,
				'user' => $user,
				'message' => 'Check-In Success!',
			], $this->successStatus);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'message' => 'Punch ' . $type . ' failed!'], $this->successStatus);
		}

	}
	public function saveCheckOut(Request $request) {
		try {

			/*if (!Entrust::can('punch')) {
				return response()->json(['success' => false, 'message' => 'Permission Denied!'], $this->successStatus);
			}*/

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
					'exists:punch_out_methods,id',
				],
			]);
			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'errors' => $validator->errors()->all(),
				], $this->successStatus);
			}
			//$user_id = Crypt::decrypt($request->qr_code);
			$user = User::with([
				'employee',
				'employee.outlet',
				'employee.designation',
			])
				->find($request->encrypted_id);
			if (!$user) {
				return response()->json([
					'success' => false,
					'message' => 'Employee not found',
				], $this->successStatus);
			}

			$attendance_log = AttendanceLog::whereDate('date', date('Y-m-d'))->where('user_id', $user->id)->whereNull('out_time')->first();
			if (!$attendance_log) {
				return response()->json([
					'success' => false,
					'message' => 'Check-In details not found',
				], $this->successStatus);
			}
			DB::beginTransaction();

			$time1 = strtotime($attendance_log->in_time);
			$time2 = strtotime(date('H:i:s'));
			if ($time2 < $time1) {
				$time2 += 86400;
			}
			$difference = date("H:i", strtotime("00:00") + ($time2 - $time1));
			$attendance_log->out_time = date('H:i:s');
			$attendance_log->duration = $difference;
			$attendance_log->punch_out_method_id = $request->punch_out_method_id;
			$attendance_log->remarks = $request->remarks;
			$attendance_log->updated_by_id = Auth::id();
			$date = date('Y-m-d');
			$attendance_log->save();

			DB::commit();
			return response()->json([
				'success' => true,
				'user' => $user,
				'attendance_log' => $attendance_log,
				'message' => 'Check-Out success!',
			], $this->successStatus);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'message' => 'Check-Out failed!',
			], $this->successStatus);
		}

	}
}
