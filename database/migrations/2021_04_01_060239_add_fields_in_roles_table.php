<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsInRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::unprepared("INSERT INTO `roles` (`id`, `role_id`, `role`, `created_at`, `updated_at`) VALUES
        (NULL, 'd42c2158-02fb-4ff1-aa6d-7c90371e6467', 'Партнер', NULL, NULL),
        (NULL, '9ac33574-e7c1-4556-8594-fa2ce6af87c1', 'Управляющий', NULL, NULL),
        (NULL, 'c0142253-59b8-4440-afe5-74d2561d4693', 'Мастер', NULL, NULL)");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('roles', function (Blueprint $table) {
            //
        });
    }
}
