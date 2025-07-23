<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsInDepartmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::unprepared("INSERT INTO `departments` (`id`, `department_id`, `department_name`, `department_address`, `time_begin`, `time_end`, `created_at`, `updated_at`) VALUES
                        (NULL, '1b8e1451-ec5a-41cf-bd98-38913361e2cd', 'Ватутина, 33', 'Ватутина, 33', NULL, NULL, NULL, NULL),
                        (NULL, '559871ac-b9d1-4dd8-b58c-69e71e90c2cc', 'Дуси Ковальчук, 185', 'Дуси Ковальчук, 185', NULL, NULL, NULL, NULL),
                        (NULL, '37857ade-4fe3-407e-a97f-75081cc7a37f', 'Красный проспект, 71', 'Красный проспект, 71', NULL, NULL, NULL, NULL),
                        (NULL, '0388493a-d104-44ad-847c-85ed48080856', 'Морской проспект, 19', 'Морской проспект, 19', NULL, NULL, NULL, NULL);
");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('departments', function (Blueprint $table) {
            //
        });
    }
}
