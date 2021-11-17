<?php

namespace Abs\EmployeePkg\Api;

use Abs\EmployeePkg\PunchOutMethod;
use App\AttendanceLog;
use App\Employee;
use App\Http\Controllers\Controller;
use App\OutletShift;
use App\Shift;
use App\User;
use App\JobCard;
use App\MechanicTimeLog;
use App\RepairOrderMechanic;
use App\JobOrderRepairOrder;
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
				// $punch_in_time = (date('h:i:s A', strtotime('+1 minutes', strtotime($punch->in_time))));
				// $current_time = date('h:i:s A');
				$punch_in_time = date('d-m-Y', strtotime($punch->date)) . ' ' .$punch->in_time;
				$punch_in_time = (date('d-m-Y h:i:s A', strtotime('+1 minutes', strtotime($punch_in_time))));
				$current_time = date('d-m-Y h:i:s A');

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
					$date = date('d-m-Y');
					$current_time = date('h:i:s A');
					$current_time = $date .' '. $current_time;

					$shift_end_time = date('h:i:s A', strtotime($shift_timing->end_time));
					if($shift_timing->is_night_shift == 1){
						$date = date('d-m-Y', strtotime("+1 days"));
					}

					$shift_end_time = $date .' '. $shift_end_time;

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

				$shift_end_time = date('Y-m-d H:i:s', strtotime($shift_timing->end_time));
			}else{
				$shift_id = '';
				if($user->employee->shift_id){
					$shift_id = $user->employee->shift_id;
				}

				$shift_end_time = Carbon::now();
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
			//Update Employee's Worklog
			$mechanic_log = MechanicTimeLog::join('repair_order_mechanics','repair_order_mechanics.id','mechanic_time_logs.repair_order_mechanic_id')
			->where('repair_order_mechanics.mechanic_id', $user->id)
			->orderBy('id','desc')
			->select('mechanic_time_logs.*')
			->first();

			if($mechanic_log){
				if(empty($mechanic_log->end_date_time)){
					$mechanic_log->end_date_time = $shift_end_time;
					$mechanic_log->status_id = 8263; //CLOSED
					$mechanic_log->is_cron_update = 1;
					$mechanic_log->save();

					//Update Work Status
					$repair_order_mechanic = RepairOrderMechanic::where('id', $mechanic_log->repair_order_mechanic_id)->where('mechanic_id', $user->id)->first();
					$repair_order_mechanic->status_id = 8263;
					$repair_order_mechanic->save();
					
					$job_order_repair_order = JobOrderRepairOrder::where('id',$repair_order_mechanic->job_order_repair_order_id)->first();

					$repair_order_status = RepairOrderMechanic::where('job_order_repair_order_id', $job_order_repair_order->id)->where('status_id', '!=', 8263)->count();

					if ($repair_order_status == 0) {
						$job_order_repair_order->status_id = 8185;
					} else {
						$job_order_repair_order->status_id = 8183;
					}
					$job_order_repair_order->save();

					$job_card = JobCard::where('job_order_id',$job_order_repair_order->job_order_id)->first();

					if ($job_card) {
						$mechanic_status = RepairOrderMechanic::join('job_order_repair_orders', 'job_order_repair_orders.id', 'repair_order_mechanics.job_order_repair_order_id')->where('job_order_repair_orders.job_order_id', $job_card->job_order_id)->where('repair_order_mechanics.status_id', '!=', 8263)->count();

						//Update Jobcard Status // Review Pending
						if ($mechanic_status == 0) {
							$job_card->status_id = 8222;
							$job_card->updated_at = Carbon::now();
							$job_card->save();
						}
					}
				}
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
