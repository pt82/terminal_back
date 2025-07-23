<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldToCoursesAndLessonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('courses', function (Blueprint $table) {
            \DB::unprepared("ALTER TABLE `courses` ADD `publish` TINYINT(0) NOT NULL DEFAULT '0' AFTER `title`;");
        });
        Schema::table('lessons', function (Blueprint $table) {
            \DB::unprepared("ALTER TABLE `lessons` ADD `publish` TINYINT(1) NULL DEFAULT '0' AFTER `description`;");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('', function (Blueprint $table) {
            //
        });
    }
}
