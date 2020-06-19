<?php

namespace Abs\EmployeePkg\Api;

use Abs\EmployeePkg\PunchOutMethod;
use App\AttendanceLog;
use App\Http\Controllers\Controller;
use App\User;
use Auth;
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
				// issue : saravanan : readability
				// return response()->json(['success' => false, 'message' => 'Validation Error', 'errors' => $validator->errors()], $this->successStatus);
			}
			$caller = Auth::user();
			// dd($caller);
			$user = User::find($request->encrypted_id);
			if (!$user) {
				// issue : saravanan : readability
				return response()->json([
					'status' => false,
					'message' => 'User not found',
				], $this->successStatus);
				// return response()->json(['status' => false, 'message' => 'Employee not found'], $this->successStatus);
			}

			$punch = AttendanceLog::whereDate('date', date('Y-m-d'))
				->where('user_id', $user->id)
				->whereNull('out_time')
				->orderBy('id', 'DESC')
				->first();

			if ($punch) {
				//PUNCH OUT
				$action = "Out";
				$data['punch_in'] = $punch;
				$data['punch_out_method_list'] = PunchOutMethod::getList();

			} else {
				//PUNCH IN
				$punch_in = new AttendanceLog();
				$punch_in->date = $date = date('Y-m-d');
				$time = date('H:i:s');
				$punch_in_time = strtotime($time);
				$punch_in->in_time = date('H:i:s', $punch_in_time);
				$punch_in->user_id = $user->id;
				$punch_in->created_by_id = Auth::id();
				$punch_in->save();
				$action = "In";
				$punch_in->in_time = date('h:i a', strtotime($punch_in->in_time));
				//dd($punch_in);
				$data['punch_in'] = $punch_in;
			}
			$data['action'] = $action;
			$data['date'] = date('Y-m-d');
			$data['time'] = date('h:i a');
			$user->employee->outlet;
			$user->role;
			$data['punch_user'] = $user;

			if ($action == 'In') {
				return response()->json([
					'success' => true,
					'message' => 'Punch ' . $action . ' success!',
					'data' => $data,
				], $this->successStatus);
			}

			return response()->json([
				'success' => true,
				'data' => $data,
			], $this->successStatus);

		} catch (Exception $e) {
			return response()->json([
				'status' => false,
				'message' => 'Punch in failed!',
			], $this->successStatus);
		}
	}

	public function savePunchOut(Request $request) {
		//dd($request->all());
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
			$time2 = strtotime(date('H:i:s'));
			if ($time2 < $time1) {
				$time2 += 86400;
			}
			$difference = date("H:i", strtotime("00:00") + ($time2 - $time1));
			$punch->out_time = date('H:i:s');
			$punch->duration = $difference;
			$punch->punch_out_method_id = $request->punch_out_method_id;
			$punch->remarks = $request->remarks;
			$punch->updated_by_id = Auth::id();
			$date = date('Y-m-d');
			$punch->save();
			$user->employee->outlet;
			$user->role;
			$data['punch_user'] = $user;
			$data['action'] = 'Out';
			$punch->in_time = date('h:i a', strtotime($punch->in_time));
			$punch->out_time = date('h:i a', strtotime($punch->out_time));
			$data['punch_out'] = $punch;

			DB::commit();
			return response()->json([
				'success' => true,
				'message' => 'Punch ' . $data['action'] . ' success!',
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
