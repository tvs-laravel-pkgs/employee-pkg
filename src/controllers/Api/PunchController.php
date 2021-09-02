<?php

namespace Abs\EmployeePkg\Api;

use Abs\EmployeePkg\PunchOutMethod;
use App\AttendanceLog;
use App\Employee;
use App\Http\Controllers\Controller;
use App\OutletShift;
use App\Shift;
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

			// $punch = AttendanceLog::whereDate('date', date('Y-m-d'))
			// 	->where('user_id', $user->id)
			// 	->whereNull('out_time')
			// 	->orderBy('id', 'DESC')
			// 	->first();
			$punch = AttendanceLog::where('user_id', $user->id)
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

				if(date("l",strtotime(date('d-m-Y'))) == 'Sunday'){
					$shift_type_id = 12282;
				}elseif(date("l",strtotime(date('d-m-Y'))) == 'Saturday'){
					$shift_type_id = 12281;
				}else{
					$shift_type_id = 12280;
				}
	
				$shift_timing = OutletShift::join('employee_shifts','employee_shifts.shift_id','outlet_shift.shift_id')->where('outlet_shift.outlet_id',$user->employee->outlet_id)->where('employee_shifts.date',date('Y-m-d'))->where('outlet_shift.shift_type_id',$shift_type_id)->where('employee_shifts.employee_id',$user->employee->id)->first();
	
				if($shift_timing){
					$shift_end_time = date('h:i:s A', strtotime($shift_timing->end_time));
					$current_time = date('h:i:s A');
	
					$shift_end_time = strtotime($shift_end_time);
					$current_time = strtotime($current_time);
					
					if ($current_time > $shift_end_time) {
						return response()->json([
							'success' => false,
							'message' => "Employee's today shift details not matched in the current time",
						], $this->successStatus);
					} 
				}
				// else{
				// 	return response()->json([
				// 		'success' => false,
				// 		'message' => 'Shift details not found',
				// 	], $this->successStatus);
				// }
				
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

			$user['shift_start_time'] = '-';
			$user['shift_end_time'] = '-';

			// $shift_timing = DB::table('outlet_shift')
			// 	->where('outlet_id', $user->employee->outlet_id)
			// 	->where('shift_id', $user->employee->shift_id)
			// 	->first();

			if(date("l",strtotime(date('d-m-Y'))) == 'Sunday'){
				$shift_type_id = 12282;
			}elseif(date("l",strtotime(date('d-m-Y'))) == 'Saturday'){
				$shift_type_id = 12281;
			}else{
				$shift_type_id = 12280;
			}

			$shift_timing = OutletShift::join('employee_shifts','employee_shifts.shift_id','outlet_shift.shift_id')->where('outlet_shift.outlet_id',$user->employee->outlet_id)->where('employee_shifts.date',date('Y-m-d'))->where('outlet_shift.shift_type_id',$shift_type_id)->where('employee_shifts.employee_id',$user->employee->id)->first(); 

			if ($shift_timing) {
				$user['shift_start_time'] = date('h:i A', strtotime($shift_timing->start_time));
				$user['shift_end_time'] = date('h:i A', strtotime($shift_timing->end_time));
				$shift_id = $shift_timing->shift_id;
			}else{
				$shift_id = '';
				if($user->employee->shift_id){
					$shift_id = $user->employee->shift_id;
				}
			}

			if ($shift_id) {
				$user['shift'] = Shift::where('id', $shift_id)->first();
			}

			$user->employee->outlet;
			$user->employee->designation;
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

			// $punch = AttendanceLog::whereDate('date', date('Y-m-d'))->where('user_id', $user->id)->whereNull('out_time')->first();
			$punch = AttendanceLog::where('user_id', $user->id)->whereNull('out_time')->orderBy('id', 'DESC')->first();
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

			$user['shift_start_time'] = '-';
			$user['shift_end_time'] = '-';
			// $shift_timing = DB::table('outlet_shift')
			// 	->where('outlet_id', $user->employee->outlet_id)
			// 	->where('shift_id', $user->employee->shift_id)
			// 	->first();
			if(date("l",strtotime(date('d-m-Y'))) == 'Sunday'){
				$shift_type_id = 12282;
			}elseif(date("l",strtotime(date('d-m-Y'))) == 'Saturday'){
				$shift_type_id = 12281;
			}else{
				$shift_type_id = 12280;
			}

			$shift_timing = OutletShift::join('employee_shifts','employee_shifts.shift_id','outlet_shift.shift_id')->where('outlet_shift.outlet_id',$user->employee->outlet_id)->where('employee_shifts.date',date('Y-m-d'))->where('outlet_shift.shift_type_id',$shift_type_id)->where('employee_shifts.employee_id',$user->employee->id)->first(); 

			if ($shift_timing) {
				$user['shift_start_time'] = date('h:i A', strtotime($shift_timing->start_time));
				$user['shift_end_time'] = date('h:i A', strtotime($shift_timing->end_time));
				$shift_id = $shift_timing->shift_id;
			}else{
				$shift_id = '';
				if($user->employee->shift_id){
					$shift_id = $user->employee->shift_id;
				}
			}

			if ($shift_id) {
				$user['shift'] = Shift::where('id', $shift_id)->first();
			}

			$user->employee->outlet;
			$user->employee->designation;
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
