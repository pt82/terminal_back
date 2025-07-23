<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateYcvatutinaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ycvatutina', function (Blueprint $table) {
            $table->id();
            $table->integer('yc_id')->nullable();
            $table->string('name',100)->nullable();
            $table->string('phone',20)->nullable();
            $table->string('email',36)->nullable();
            $table->json('categories')->nullable();
            $table->integer('sex_id')->nullable();
            $table->string('sex',36)->nullable();
            $table->string('birth_date',50)->nullable();
            $table->integer('discount')->nullable();
            $table->string('card',100)->nullable();
            $table->string('comment',400)->nullable();
            $table->integer('sms_check')->nullable();
            $table->integer('sms_bot')->nullable();
            $table->integer('spent')->nullable();
            $table->integer('paid')->nullable();
            $table->integer('balance')->nullable();
            $table->integer('visits')->nullable();
            $table->integer('importance_id')->nullable();
            $table->dateTime('last_change_date')->nullable();
            $table->string('importance',30)->nullable();
            $table->json('custom_fields')->nullable();


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
        Schema::dropIfExists('ycvatutina');
    }
}
