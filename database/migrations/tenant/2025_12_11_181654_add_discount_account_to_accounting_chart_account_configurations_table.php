<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\Accounting\Models\ChartOfAccount;

class AddDiscountAccountToAccountingChartAccountConfigurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Agregar columna a la tabla de configuración
        Schema::table('accounting_chart_account_configurations', function (Blueprint $table) {
            $table->string('discount_account',10)->nullable()->after('payroll_account');
        });

        // 2. Buscar el parent_id de la cuenta 4210
        $parent = ChartOfAccount::where('code', '4210')->first();

        // 3. Crear la cuenta 421040 si no existe
        if ($parent && !ChartOfAccount::where('code', '421040')->exists()) {
            ChartOfAccount::create([
                'code'      => '421040',
                'name'      => 'Descuentos comerciales condicionados',
                'type'      => 'Revenue',
                'level'     => 4,
                'parent_id' => $parent->id,
                'status'    => 1,
            ]);
        }

        $discountAccount = ChartOfAccount::where('code', '421040')->first();
        if ($discountAccount) {
            \DB::table('accounting_chart_account_configurations')->update([
                'discount_account' => $discountAccount->code
            ]);
        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Eliminar la columna
        Schema::table('accounting_chart_account_configurations', function (Blueprint $table) {
            $table->dropColumn('discount_account');
        });

        // Eliminar la cuenta 421040
        ChartOfAccount::where('code', '421040')->delete();
    }
}
