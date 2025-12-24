<?php
namespace App\Http\Controllers\Tenant\src\Http\Resources\csv;


class TaxCsvReosurce
{

    public static function toCsv($taxes)
    {
        $columns = [
            'id',
            'name',
            'code',
            'rate',
            'conversion',
            'is_percentage',
            'is_fixed_value',
            'is_retention',
            'in_base',
            'in_tax',
            'deleted_at',
            'created_at',
            'updated_at',
            'type_tax_id',
            'chart_account_sale',
            'chart_account_purchase',
            'chart_account_return_sale',
            'chart_account_return_purchase',
        ];
        $callback = function() use ($taxes, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($taxes as $tax) {
                $row = [];
                foreach ($columns as $column) {
                    $row[] = $tax[$column];
                }
                fputcsv($file, $row);
            }

            fclose($file);
        };
        return $callback;
    }

}
