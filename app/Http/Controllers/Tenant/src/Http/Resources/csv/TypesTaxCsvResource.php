<?php
namespace App\Http\Controllers\Tenant\src\Http\Resources\csv;


class TypesTaxCsvResource
{

    public static function toCsv($taxes)
    {
        $columns = [
            'id',
            'name',
            'description',
            'code',
            'created_at',
            'updated_at',
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
