<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsTimezoneToDepartmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('departments', function (Blueprint $table) {
            \DB::unprepared('ALTER TABLE `departments` ADD `timezone_offset` INT(3) NULL DEFAULT NULL AFTER `department_address`, ADD `timezone_title` VARCHAR(50) NULL DEFAULT NULL AFTER `timezone_offset`;');
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
