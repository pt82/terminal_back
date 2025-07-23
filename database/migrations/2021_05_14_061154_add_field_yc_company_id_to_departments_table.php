<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldYcCompanyIdToDepartmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('departments', function (Blueprint $table) {
            \DB::unprepared('ALTER TABLE `departments` ADD `yc_company_id` INT NULL DEFAULT NULL AFTER `department_id`;');
        });

        Schema::table('chains', function (Blueprint $table) {
            \DB::unprepared('ALTER TABLE `chains` ADD `yc_login` VARCHAR(50) NULL DEFAULT NULL AFTER `name`, ADD `yc_password` VARCHAR(100) NULL DEFAULT NULL AFTER `yc_login`;');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('departments', function (Blueprint $table) {
            //
        });
    }
}
