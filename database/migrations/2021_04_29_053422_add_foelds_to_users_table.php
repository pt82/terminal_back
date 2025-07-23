<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFoeldsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            \DB::unprepared("ALTER TABLE `users` ADD `near_brday` DATE NULL DEFAULT NULL AFTER `email`, ADD `rating` INT(2) NULL DEFAULT NULL AFTER `near_brday`, ADD `activity` VARCHAR(50) NULL DEFAULT NULL AFTER `rating`, ADD `software` TINYINT(1) NULL DEFAULT NULL AFTER `activity`;");
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
