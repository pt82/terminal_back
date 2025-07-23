<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldRecordFromYcrecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ycrecords', function (Blueprint $table) {
            $table->string('record_from')->nullable()->after('bookform_id');
            $table->integer('is_mobile')->nullable()->after('bookform_id');
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
