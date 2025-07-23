<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateYctransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('yctransactions', function (Blueprint $table) {
            $table->id();
            $table->integer('transaction_id')->nullable()->unique();

            $table->bigInteger('user_id')->nullable()->unsigned();
            $table->foreign('user_id')->references('id')->on('users');

            $table->bigInteger('department_id')->nullable()->unsigned();
            $table->foreign('department_id')->references('id')->on('departments');

            $table->bigInteger('chain_id')->nullable()->unsigned();
            $table->foreign('chain_id')->references('id')->on('chains');

            $table->bigInteger('record_id')->nullable()->unsigned();
            $table->foreign('record_id')->references('id')->on('ycrecords');

            $table->json('expense')->nullable();
            $table->dateTimeTz('date')->nullable();
            $table->integer('amount')->nullable();
            $table->string('comment',2000)->nullable();
            $table->json('master')->nullable();
            $table->json('supplier')->nullable();
            $table->json('account')->nullable();
            $table->json('client')->nullable();
            $table->dateTimeTz('last_change_date')->nullable();
            $table->integer('visit_id')->nullable();
            $table->integer('sold_item_id')->nullable();
            $table->string('sold_item_type')->nullable();

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
        Schema::dropIfExists('yctransactions');
    }
}
