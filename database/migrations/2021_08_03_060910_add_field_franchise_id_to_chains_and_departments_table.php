<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldFranchiseIdToChainsAndDepartmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('chains_and_departments', function (Blueprint $table) {
            Schema::table('chains', function (Blueprint $table) {
                $table->unsignedBigInteger('franchise_id')->nullable()->unsigned()->after('id');
                $table->foreign('franchise_id')->references('id')->on('franchises');
            });
            Schema::table('departments', function (Blueprint $table) {
                $table->unsignedBigInteger('franchise_id')->nullable()->unsigned()->after('id');
                $table->foreign('franchise_id')->references('id')->on('franchises');
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('chains_and_departments', function (Blueprint $table) {
            //
        });
    }
}
