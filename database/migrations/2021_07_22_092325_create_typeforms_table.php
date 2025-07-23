<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTypeformsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('typeforms', function (Blueprint $table) {
            $table->id();
            $table->string('form_id',20);
            $table->unsignedBigInteger('ycrecord_id')->nullable()->unsigned();
            $table->foreign('ycrecord_id')->references('id')->on('ycrecords');
            $table->string('title',50)->nullable();
            $table->json('answers')->nullable();
            $table->json('definition')->nullable();
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
        Schema::dropIfExists('typeforms');
    }
}
