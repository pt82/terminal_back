<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateYcitemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
           Schema::create('ycitems', function (Blueprint $table) {
                $table->id();
                $table->string('ycitem_id',36)->unique();
                $table->string('person_id',36)->nullable();
                $table->foreign('person_id')->references('person_id')->on('users');
                $table->string('department_id', 36)->nullable();
                $table->foreign('department_id')->references('department_id')->on('departments');
                $table->string('chain_id', 36)->nullable();
                $table->foreign('chain_id')->references('chain_id')->on('chains');
                $table->bigInteger('yc_id')->nullable();
                $table->string('name',100)->nullable();
                $table->string('phone',20)->nullable();
                $table->string('email',35)->nullable();
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
                $table->string('last_change_date',30)->nullable();
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
        Schema::dropIfExists('ycitems');
    }
}
