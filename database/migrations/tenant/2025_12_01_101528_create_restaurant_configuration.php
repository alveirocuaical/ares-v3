<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateRestaurantConfiguration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('restaurant_configurations', function (Blueprint $table) {
            $table->increments('id');
            $table->boolean('menu_pos');
            $table->boolean('menu_order');
            $table->boolean('menu_tables');
            $table->string('first_menu');
            $table->integer('tables_quantity')->default(15);
            $table->boolean('menu_bar')->default(true);
            $table->boolean('menu_kitchen')->default(true);
            $table->boolean('enabled_environment_1')->default(true);
            $table->boolean('enabled_environment_2')->default(false);
            $table->boolean('enabled_environment_3')->default(false);
            $table->integer('tables_quantity_environment_2')->default(5);
            $table->integer('tables_quantity_environment_3')->default(5);
            $table->boolean('enabled_environment_4')->default(false);
            $table->integer('tables_quantity_environment_4')->default(5);
            $table->boolean('items_maintenance')->default(false);
            $table->boolean('enabled_send_command')->default(false);
            $table->boolean('enabled_print_command')->default(true);
            $table->boolean('enabled_printsend_command')->default(false);
            $table->boolean('enabled_command_waiter')->default(false);
            $table->boolean('enabled_pos_waiter')->default(false);
            $table->boolean('enabled_close_table')->default(true);
        });

        DB::table('restaurant_configurations')->insert([
            [
                'menu_pos' => true,
                'menu_order' => true,
                'menu_tables' => true,
                'first_menu' => 'POS',
                'tables_quantity' => 15,
                'menu_bar' => true,
                'menu_kitchen' => true,
                'enabled_environment_1' => true,
                'enabled_environment_2' => false,
                'enabled_environment_3' => false,
                'tables_quantity_environment_2' => 5,
                'tables_quantity_environment_3' => 5,
                'enabled_environment_4' => false,
                'tables_quantity_environment_4' => 5,
                'items_maintenance' => false,
                'enabled_send_command' => false,
                'enabled_print_command' => true,
                'enabled_printsend_command' => false,
                'enabled_command_waiter' => false,
                'enabled_pos_waiter' => false,
                'enabled_close_table' => true,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('full_restaurant_configuration');
    }
}
