<?php

namespace Abs\EmployeePkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SkillLevel extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'skill_levels';
	public $timestamps = true;
	protected $fillable = [
		'company_id',
		'short_name',
		'name',
		'description',
	];

	protected static $excelColumnRules = [
		'Short Name' => [
			'table_column_name' => 'short_name',
			'rules' => [
				'required' => [
				],
			],
		],
		'Name' => [
			'table_column_name' => 'name',
			'rules' => [
				'nullable' => [
				],
			],
		],
		'Description' => [
			'table_column_name' => 'description',
			'rules' => [
				'nullable' => [
				],
			],
		],
	];
	// Query Scopes --------------------------------------------------------------

	public function scopeFilterSearch($query, $term) {
		if (strlen($term)) {
			$query->where(function ($query) use ($term) {
				$query->orWhere('code', 'LIKE', '%' . $term . '%');
				$query->orWhere('name', 'LIKE', '%' . $term . '%');
			});
		}
	}

	// Relations --------------------------------------------------------------

	public function employees() {
		return $this->hasMany('App\Employee', 'skill_level_id');
	}

	// Static Operations --------------------------------------------------------------

	public static function saveFromObject($record_data) {
		$record = [
			'Company Code' => $record_data->company_code,
			'Short Name' => $record_data->short_name,
			'Name' => $record_data->name,
			'Description' => $record_data->description,
		];
		return static::saveFromExcelArray($record);
	}

	public static function saveFromExcelArray($record_data) {
		try {
			$errors = [];
			$company = Company::where('code', $record_data['Company Code'])->first();
			if (!$company) {
				return [
					'success' => false,
					'errors' => ['Invalid Company : ' . $record_data['Company Code']],
				];
			}

			if (!isset($record_data['created_by_id'])) {
				$admin = $company->admin();

				if (!$admin) {
					return [
						'success' => false,
						'errors' => ['Default Admin user not found'],
					];
				}
				$created_by_id = $admin->id;
			} else {
				$created_by_id = $record_data['created_by_id'];
			}

			$record = self::firstOrNew([
				'company_id' => $company->id,
				'short_name' => $record_data['Short Name'],
			]);

			$result = Self::validateAndFillExcelColumns($record_data, Static::$excelColumnRules, $record);
			if (!$result['success']) {
				return $result;
			}
			$record->created_by_id = $created_by_id;
			$record->save();
			return [
				'success' => true,
			];
		} catch (\Exception $e) {
			return [
				'success' => false,
				'errors' => [$e->getMessage()],
			];

		}
	}
}
