<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DelRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::unprepared("DROP TABLE `bis`.`role_user`");
        \DB::unprepared("ALTER TABLE bis.users DROP FOREIGN KEY users_roles_id_foreign");
        \DB::unprepared("DROP TABLE `bis`.`roles`");

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
