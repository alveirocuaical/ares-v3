<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateRestaurantTableEnvsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('restaurant_table_envs', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary()->unique();
            $table->string('name');
            $table->boolean('active');
            $table->integer('tables_quantity');
        });

        DB::table('restaurant_table_envs')->insert([
            ['id' => 1, 'active' => true, 'name' => 'Ambiente 1', 'tables_quantity' => 25],
            ['id' => 2, 'active' => true, 'name' => 'Ambiente 2', 'tables_quantity' => 25],
            ['id' => 3, 'active' => true, 'name' => 'Ambiente 3', 'tables_quantity' => 25],
            ['id' => 4, 'active' => true, 'name' => 'Ambiente 4', 'tables_quantity' => 25],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('restaurant_table_envs');
    }
}
