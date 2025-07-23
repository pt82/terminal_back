<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldServicecategoriesIdToServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ycservices', function (Blueprint $table) {
            $table->bigInteger('servicecategory_id')->nullable()->unsigned();
            $table->foreign('servicecategory_id')->references('id')->on('servicecategories');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ycservices', function (Blueprint $table) {
            //
        });
    }
}
