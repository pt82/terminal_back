<?php

use App\Models\Ycrecord;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldRecordDoneToYcrecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ycrecords', function (Blueprint $table) {
            $table->boolean('record_done')->nullable()->default(0);
            });
        Ycrecord::where('date','<','2021-06-30 00:00:00')->update(['record_done'=>1]);
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
