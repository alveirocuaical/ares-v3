<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateAccount24080501Text extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $account = DB::table('chart_of_accounts')->where('code', '24080501')->first();

        if ($account) {
            DB::table('chart_of_accounts')
                ->where('code', '24080501')
                ->update(['name' => 'IVA generado 19%']);
        }
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
