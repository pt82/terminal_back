<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsLoginPasswordUserTokenToFranchisesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('franchises', function (Blueprint $table) {
            \DB::unprepared('ALTER TABLE `franchises` ADD `login` VARCHAR(20) NULL DEFAULT NULL AFTER `name`, ADD `password` VARCHAR(60) NULL DEFAULT NULL AFTER `login`, ADD `user_token` VARCHAR(36) NOT NULL AFTER `password`;');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('franchises', function (Blueprint $table) {
            //
        });
    }
}
