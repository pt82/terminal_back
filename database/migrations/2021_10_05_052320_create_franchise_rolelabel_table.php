<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFranchiseRolelabelTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('franchise_rolelabel', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('franchise_id')->unsigned();
            $table->foreign('franchise_id')->references('id')->on('franchises');
            $table->unsignedBigInteger('rolelabel_id')->unsigned();
            $table->foreign('rolelabel_id')->references('id')->on('rolelabels');
            $table->unsignedInteger('role_id')->unsigned();
            $table->foreign('role_id')->references('id')->on('roles');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('role_rolelabel');
    }
}
