<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AttendanceLogsC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		if (!Schema::hasTable('attendance_logs')) {
			Schema::create('attendance_logs', function (Blueprint $table) {
				$table->increments('id');
				$table->unsignedInteger('user_id');
				$table->date('date');
				$table->time('in_time');
				$table->time('out_time')->nullable();
				$table->time('duration')->nullable();
				$table->unsignedInteger('punch_out_method_id')->nullable();
				$table->string('remarks', 255)->nullable();
				$table->unsignedInteger('created_by_id')->nullable();
				$table->unsignedInteger('updated_by_id')->nullable();
				$table->unsignedInteger('deleted_by_id')->nullable();
				$table->timestamps();
				$table->softDeletes();

				$table->foreign('punch_out_method_id')->references('id')->on('punch_out_methods')->onDelete('SET NULL')->onUpdate('cascade');
				$table->foreign('user_id')->references('id')->on('users')->onDelete('CASCADE')->onUpdate('cascade');
				$table->foreign('created_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
				$table->foreign('updated_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
				$table->foreign('deleted_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('attendance_logs');
	}
}
