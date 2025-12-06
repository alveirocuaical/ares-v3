<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateRestaurantRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('restaurant_roles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code');
            $table->string('name');
            $table->string('description');
            $table->timestamps();
        });

        DB::table('restaurant_roles')->insert([
            [
                'name' => 'Mozo',
                'code' => 'MOZO',
                'description' => 'Usuario que genera pedidos en mesas',
            ],
            [
                'code' => 'CAJA',
                'name' => 'Caja',
                'description' => 'Usuario que genera pago de pedidos',
            ],
            [
                'code' => 'ADM',
                'name' => 'Administrador',
                'description' => 'Usuario con permisos totales',
            ],
            [
                'code' => 'KITBAR',
                'name' => 'Cocina/Bar',
                'description' => 'Usuario con acceso a cocina y bar',
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
        Schema::dropIfExists('restaurant_roles');
    }
}
