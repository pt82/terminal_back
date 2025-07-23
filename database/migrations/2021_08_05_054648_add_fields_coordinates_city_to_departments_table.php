<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsCoordinatesCityToDepartmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('departments', function (Blueprint $table) {
//            \DB::unprepared('ALTER TABLE `departments` ADD `coordinates` JSON NULL DEFAULT NULL AFTER `timezone_title`, ADD `city` VARCHAR(30) NULL DEFAULT NULL AFTER `coordinates`;');
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
