<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldPersonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::unprepared("ALTER TABLE `persons`
                        ADD `uc_id` BIGINT NULL DEFAULT NULL AFTER `email`,
                        ADD `uc_name` VARCHAR(50) NULL DEFAULT NULL AFTER `uc_id`,
                        ADD `sex` VARCHAR(15) NULL DEFAULT NULL AFTER `uc_name`,
                        ADD `comment` VARCHAR(200) NULL DEFAULT NULL AFTER `sex`;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
