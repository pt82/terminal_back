<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddValItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::unprepared("INSERT INTO `items` (`id`, `item_id`, `name`, `city`, `adress`, `created_at`, `updated_at`)
                        VALUES (NULL, 'b61b735c-b19b-4c00-a63e-ac9ec18f8b4b', 'Клиент', 'Новосибирск', 'Ватутина, 33', '2021-04-07 11:07:53', '2021-04-07 11:07:53')");

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
