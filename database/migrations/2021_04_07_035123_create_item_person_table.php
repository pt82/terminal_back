<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemPersonTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('item_person', function (Blueprint $table) {
            $table->id();
            $table->string('person_id',36)->nullable();
            $table->string('item_id',36)->nullable();
            $table->foreign('person_id')->references('person_id')->on('persons');
            $table->foreign('item_id')->references('item_id')->on('items');
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
        Schema::dropIfExists('item_person');
    }
}
