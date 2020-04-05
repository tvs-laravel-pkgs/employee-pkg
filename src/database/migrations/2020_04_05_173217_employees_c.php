<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EmployeesC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		if (!Schema::hasTable('employees')) {
			Schema::create('employees', function (Blueprint $table) {
				$table->increments('id');
				$table->unsignedInteger('company_id');
				$table->string('code', 191);
				$table->unsignedInteger('designation_id')->nullable();
				$table->string('github_username', 64)->nullable();
				$table->string('personal_email', 64)->nullable();
				$table->string('alternate_mobile_number', 10)->nullable();
				$table->timestamps();
				$table->softDeletes();

				$table->foreign('company_id')->references('id')->on('companies')->onDelete('CASCADE')->onUpdate('cascade');
				$table->foreign('designation_id')->references('id')->on('designations')->onDelete('SET NULL')->onUpdate('cascade');

				$table->unique(["company_id", "code"]);
				$table->unique(["company_id", "personal_email"]);
			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('employees');
	}
}
