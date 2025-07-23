<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldYcStaffIdToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            \DB::unprepared ('ALTER TABLE `users` ADD `yc_staff_id` INT(10) NULL DEFAULT NULL AFTER `person_ivideon_id`;');
            \DB::unprepared ('ALTER TABLE `users` ADD `comments` JSON NULL DEFAULT NULL AFTER `comment`;');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
}
