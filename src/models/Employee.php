<?php

namespace Abs\EmployeePkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use App\Company;
use App\Config;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'employees';
	public $timestamps = true;
	protected $fillable = [
		'code',
		'designation_id',
		'github_username',
		'date_of_join',
	];

	// Getters --------------------------------------------------------------

	public function getDateOfJoinAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	// Setters --------------------------------------------------------------

	public function setDateOfJoinAttribute($date) {
		return $this->attributes['date_of_join'] = empty($date) ? NULL : date('Y-m-d', strtotime($date));
	}

	// Relationships --------------------------------------------------------------

	public function user() {
		return $this->hasOne('App\User', 'entity_id')->where('users.user_type_id', 1);
	}

	public function designation() {
		return $this->belongsTo('App\Designation', 'designation_id');
	}

	public function employeeAttachment() {
		return $this->hasOne('Abs\BasicPkg\Attachment', 'entity_id')->where('attachment_of_id', 120)->where('attachment_type_id', 140);
	}

	// Static Operations --------------------------------------------------------------

	public static function createFromObject($record_data) {

		$errors = [];
		$company = Company::where('code', $record_data->company)->first();
		if (!$company) {
			dump('Invalid Company : ' . $record_data->company);
			return;
		}

		$admin = $company->admin();
		if (!$admin) {
			dump('Default Admin user not found');
			return;
		}

		$type = Config::where('name', $record_data->type)->where('config_type_id', 89)->first();
		if (!$type) {
			$errors[] = 'Invalid Tax Type : ' . $record_data->type;
		}

		if (count($errors) > 0) {
			dump($errors);
			return;
		}

		$record = self::firstOrNew([
			'company_id' => $company->id,
			'name' => $record_data->tax_name,
		]);
		$record->type_id = $type->id;
		$record->created_by_id = $admin->id;
		$record->save();
		return $record;
	}

}
