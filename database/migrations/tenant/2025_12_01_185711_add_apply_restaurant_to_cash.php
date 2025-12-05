<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddApplyRestaurantToCash extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cash', function (Blueprint $table) {
            $table->boolean('apply_restaurant')->default(false)->after('reference_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cash', function (Blueprint $table) {
            $table->dropColumn('apply_restaurant');
        });
    }
}
