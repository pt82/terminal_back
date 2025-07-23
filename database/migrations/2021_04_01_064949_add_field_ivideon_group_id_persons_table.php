<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldIvideonGroupIdPersonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('persons', function (Blueprint $table) {
            $table->string('ivideon_group_id',36);
            $table->foreign('ivideon_group_id')->references('ivideon_groups_id')->on('ivideon_groups');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('persons', function ($table) {
            $table->dropForeign('persons_ivideon_groups_id_foreign');
            $table->dropColumn('ivideon_group_id');
        });
    }
}
