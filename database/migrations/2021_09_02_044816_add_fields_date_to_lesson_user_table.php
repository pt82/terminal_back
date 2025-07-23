<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsDateToLessonUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lesson_user', function (Blueprint $table) {
            \DB::unprepared('ALTER TABLE `lesson_user` ADD `date_begin` TIMESTAMP NULL DEFAULT NULL AFTER `user_id`,
                            ADD `date_end` TIMESTAMP NULL DEFAULT NULL AFTER `date_begin`;');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lesson_user', function (Blueprint $table) {
            //
        });
    }
}
