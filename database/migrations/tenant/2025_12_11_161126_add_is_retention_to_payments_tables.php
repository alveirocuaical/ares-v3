<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsRetentionToPaymentsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('document_payments', function (Blueprint $table) {
            $table->boolean('is_retention')->default(false)->after('payment');
        });
        Schema::table('purchase_payments', function (Blueprint $table) {
            $table->boolean('is_retention')->default(false)->after('payment');
        });
        Schema::table('support_document_payments', function (Blueprint $table) {
            $table->boolean('is_retention')->default(false)->after('payment');
        });
        Schema::table('expense_payments', function (Blueprint $table) {
            $table->boolean('is_retention')->default(false)->after('payment');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('document_payments', function (Blueprint $table) {
            $table->dropColumn('is_retention');
        });
        Schema::table('purchase_payments', function (Blueprint $table) {
            $table->dropColumn('is_retention');
        });
        Schema::table('support_document_payments', function (Blueprint $table) {
            $table->dropColumn('is_retention');
        });
        Schema::table('expense_payments', function (Blueprint $table) {
            $table->dropColumn('is_retention');
        });
    }
}
