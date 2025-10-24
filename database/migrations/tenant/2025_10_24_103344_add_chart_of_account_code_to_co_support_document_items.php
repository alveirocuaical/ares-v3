<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddChartOfAccountCodeToCoSupportDocumentItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('co_support_document_items', function (Blueprint $table) {
            $table->string('chart_of_account_code', 20)->nullable()->after('discount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('co_support_document_items', function (Blueprint $table) {
            $table->dropColumn('chart_of_account_code');
        });
    }
}
