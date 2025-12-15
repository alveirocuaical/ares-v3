<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTypeRetentionOnPayments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('document_payments', 'retention_type_id')) {
            Schema::table('document_payments', function (Blueprint $table) {
                $table->unsignedInteger('retention_type_id')->nullable()->after('is_retention');
                $table->foreign('retention_type_id')->references('id')->on('co_taxes')->onDelete('set null');
            });
        }

        if (!Schema::hasColumn('purchase_payments', 'retention_type_id')) {
            Schema::table('purchase_payments', function (Blueprint $table) {
                $table->unsignedInteger('retention_type_id')->nullable()->after('is_retention');
                $table->foreign('retention_type_id')->references('id')->on('co_taxes')->onDelete('set null');
            });
        }

        if (!Schema::hasColumn('support_document_payments', 'retention_type_id')) {
            Schema::table('support_document_payments', function (Blueprint $table) {
                $table->unsignedInteger('retention_type_id')->nullable()->after('is_retention');
                $table->foreign('retention_type_id')->references('id')->on('co_taxes')->onDelete('set null');
            });
        }

        if (!Schema::hasColumn('expense_payments', 'retention_type_id')) {
            Schema::table('expense_payments', function (Blueprint $table) {
                $table->unsignedInteger('retention_type_id')->nullable()->after('is_retention');
                $table->foreign('retention_type_id')->references('id')->on('co_taxes')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('document_payments', 'retention_type_id')) {
            Schema::table('document_payments', function (Blueprint $table) {
                $table->dropForeign(['retention_type_id']);
                $table->dropColumn('retention_type_id');
            });
        }

        if (Schema::hasColumn('purchase_payments', 'retention_type_id')) {
            Schema::table('purchase_payments', function (Blueprint $table) {
                $table->dropForeign(['retention_type_id']);
                $table->dropColumn('retention_type_id');
            });
        }

        if (Schema::hasColumn('support_document_payments', 'retention_type_id')) {
            Schema::table('support_document_payments', function (Blueprint $table) {
                $table->dropForeign(['retention_type_id']);
                $table->dropColumn('retention_type_id');
            });
        }

        if (Schema::hasColumn('expense_payments', 'retention_type_id')) {
            Schema::table('expense_payments', function (Blueprint $table) {
                $table->dropForeign(['retention_type_id']);
                $table->dropColumn('retention_type_id');
            });
        }
    }
}
