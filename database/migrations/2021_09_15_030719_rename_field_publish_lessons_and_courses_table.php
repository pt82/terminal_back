<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameFieldPublishLessonsAndCoursesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lessons', function (Blueprint $table) {
            DB::unprepared("ALTER TABLE `lessons` CHANGE `publish` `published` TINYINT(1) NULL DEFAULT '0';");
        });

        Schema::table('courses', function (Blueprint $table) {
            DB::unprepared("ALTER TABLE `courses` CHANGE `publish` `published` TINYINT NOT NULL DEFAULT '0';");
        });
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
