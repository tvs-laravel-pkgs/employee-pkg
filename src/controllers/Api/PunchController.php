<?php

namespace Abs\EmployeePkg\Api;

use App\AttendanceLog;
use App\Http\Controllers\Controller;
use App\User;
use Abs\EmployeePkg\PunchOutMethod;
use Auth;
use DB;
use Illuminate\Http\Request;
use Validator;

class PunchController extends Controller {
	public $successStatus = 200;

	public function status(Request $request) {
		try {

			/*if (!Entrust::can('punch')) {
				return response()->json(['success' => false, 'message' => 'Permission Denied!'], $this->successStatus);
			}*/

			$validator = Validator::make($request->all(), [
				'encrypted_id' =>[
				 	'required',
				 	'numeric',
				 	'exists:users,id',
				 ],
			]);
			if ($validator->fails()) {
				// dd($validator->errors()->all()->toArray());
				return response()->json([
					'success' => false,
					'message' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				], $this->successStatus);
				// issue : saravanan : readability
				// return response()->json(['success' => false, 'message' => 'Validation Error', 'errors' => $validator->errors()], $this->successStatus);
			}
			$logged_user = User::find(Auth::user()->id);
			if (!$logged_user) {
				return response()->json([
					'status' => false,
					'message' => 'Logged user not found',
				], $this->successStatus);
			}
			$logged_user->employee->outlet;

			$user = User::find($request->encrypted_id);
			if (!$user) {
				// issue : saravanan : readability
				return response()->json([
					'status' => false,
					'message' => 'Employee not found',
				], $this->successStatus);
				// return response()->json(['status' => false, 'message' => 'Employee not found'], $this->successStatus);
			}

			$punch = AttendanceLog::whereDate('date', date('Y-m-d'))->where('user_id', $user->id)->orderBy('id', 'DESC')->first();
			if ($punch) {
				if (!$punch->out_time) {
					$action = "Out";
					$data['punch_in'] = $punch;
					$data['punch_out_method_list']=PunchOutMethod::getList();
				} else {
					$action = "In";
				}
			} else {
				$action = "In";
			}
			$data['action'] = $action;
			$data['date'] = date('Y-m-d');
			$data['time'] = date('h:i a');
			//$user->profile_image_url = $user->profile_image_url;
			$data['user'] = $user;
			$data['logged_user'] = $logged_user;
			//$data['punch_out_method_list']=PunchOutMethod::getList();
			//dd($data);
			return response()->json(['success' => true, 'data' => $data], $this->successStatus);
		} catch (Exception $e) {
			return response()->json(['status' => false, 'message' => 'Punch ' . $type . ' failed!'], $this->successStatus);
		}
	}

	public function savePunchIn(Request $request) {
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
			$user = User::find($request->encrypted_id);
			//dd($user);
			if (!$user) {
				return response()->json([
					'success' => false, 'message' => 'Employee not found'], $this->successStatus);
			}

			//$logged_in_by_id = $request->user()->id;
			//dd($logged_in_by_id);
			DB::beginTransaction();

			$punch = AttendanceLog::whereDate('date', date('Y-m-d'))->where('user_id', $user->id)->whereNull('out_time')->first();
			if($punch){
				return response()->json([
					'success' => false,
					 'message' => 'Punch in details already exists'
				], $this->successStatus);
			}
		
			$punch_in = new AttendanceLog();
			$punch_in->date = $date = date('Y-m-d');
			$time = date('H:i:s');
			$punch_in_time = strtotime($time) - (10 * 60);
			$punch_in->in_time = date('H:i:s', $punch_in_time);
			$punch_in->user_id = $user->id;
			$punch_in->created_by_id = Auth::id();
			$punch_in->save();
			$type = 'In';
			$data['user'] = $user;
			$data['action'] = 'In';
			DB::commit();
			return response()->json([
				'success' => true, 
				'message' => 'Punch ' . $type . ' success!',
				'data'=>$data,
			], $this->successStatus);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'message' => 'Punch ' . $type . ' failed!'], $this->successStatus);
		}

	}
	public function savePunchOut(Request $request) {
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
				'punch_out_method_id' =>[
				 	'required',
				 	'numeric',
				 	'exists:punch_out_methods,id',
				],
				'remarks' =>[
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
			$user = User::find($request->encrypted_id);
			//dd($user);
			if (!$user) {
				return response()->json([
					'success' => false,
					 'message' => 'Employee not found'
				], $this->successStatus);
			}

			//$logged_in_by_id = $request->user()->id;
			//dd($logged_in_by_id);

			$punch = AttendanceLog::whereDate('date', date('Y-m-d'))->where('user_id', $user->id)->whereNull('out_time')->first();
			if (!$punch) {
				return response()->json([
					'success' => false,
					 'message' => 'Punch in details not found'
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
				$type = 'Out';
				$data['user'] = $user;
				$data['action'] = 'Out';
				$data['punch_out'] = $punch;

			DB::commit();
			return response()->json([
				'success' => true,
				'message' => 'Punch ' . $type . ' success!',
				'data'=>$data
			], $this->successStatus);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				 'message' => 'Punch ' . $type . ' failed!'
			], $this->successStatus);
		}

	}
}
