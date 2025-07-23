<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChainUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chain_user', function (Blueprint $table) {
            $table->id();
            $table->string('person_id',36)->nullable();
            $table->string('chain_id',36)->nullable();
            $table->foreign('person_id')->references('person_id')->on('users');
            $table->foreign('chain_id')->references('chain_id')->on('chains');
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
        Schema::dropIfExists('chain_user');
    }
}
