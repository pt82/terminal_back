<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldTypeformStatusToYcrecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ycrecords', function (Blueprint $table) {
            \DB::unprepared('ALTER TABLE `ycrecords` ADD `typeform_status` INT(1) NULL DEFAULT 0 AFTER `rating`;');
        });
        Schema::table('franchises', function (Blueprint $table) {
            \DB::unprepared('ALTER TABLE `franchises` ADD `use_system` VARCHAR(20) NULL DEFAULT NULL AFTER `name`;');
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
