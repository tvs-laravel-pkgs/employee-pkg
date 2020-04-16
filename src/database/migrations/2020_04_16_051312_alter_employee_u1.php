<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterEmployeeU1 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
           $table->dropUnique('employees_company_id_personal_email_unique');
           $table->dropColumn('personal_email');
           $table->dropColumn('alternate_mobile_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
             $table->string('personal_email',64)->nullable()->after('github_username');
             $table->string('alternate_mobile_number',10)->nullable()->after('personal_email');
             $table->unique(['company_id','personal_email']);
        });
    }
}
