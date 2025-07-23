<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStaffCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('staff_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->unsigned();
            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('chain_id')->nullable()->unsigned();
            $table->foreign('chain_id')->references('id')->on('chains');
            $table->unsignedBigInteger('department_id')->nullable()->unsigned();
            $table->foreign('department_id')->references('id')->on('departments');
            $table->unsignedBigInteger('yc_id')->nullable();
            $table->unsignedBigInteger('salon_id')->nullable();
            $table->unsignedBigInteger('type')->nullable();
            $table->unsignedBigInteger('master_id')->nullable();
            $table->string('text',2000)->nullable();
            $table->dateTimeTz('date')->nullable();
            $table->integer('rating')->nullable();
            $table->unsignedBigInteger('yc_user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('user_avatar')->nullable();
            $table->string('user_email')->nullable();
            $table->string('user_phone')->nullable();
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
        Schema::dropIfExists('staff_comments');
    }
}
