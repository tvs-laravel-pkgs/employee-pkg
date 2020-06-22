<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AttendanceLogU extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('attendance_logs', function (Blueprint $table) {
			$table->unsignedInteger('punch_in_outlet_id')->nullable()->after('duration');

			$table->foreign('punch_in_outlet_id')->references('id')->on('outlets')->onDelete('SET NULL')->onUpdate('cascade');
		});

	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('attendance_logs', function (Blueprint $table) {
			$table->dropForeign('attendance_logs_punch_in_outlet_id_foreign');

			$table->dropColumn('punch_in_outlet_id');
		});

	}
}
