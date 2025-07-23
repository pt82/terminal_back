<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldSmsRemainHoursToYcrecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ycrecords', function (Blueprint $table) {
            \DB::unprepared('ALTER TABLE `ycrecords` ADD `email_remain_hours` INT NULL DEFAULT NULL AFTER `custom_fields`;');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ycrecords', function (Blueprint $table) {
            //
        });
    }
}
