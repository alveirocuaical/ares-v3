<?php
namespace App\Http\Controllers\Tenant\src\Http\Resources\csv;

class PaymentMethodCsvResource
{
    public static function toCsv($paymentMethods)
    {
        $columns = [
            "id",
            "name",
            "code",

        ];

        $callback = function() use ($paymentMethods, $columns) {
            $file = fopen('php://output', 'w');
            // Manually write header with quotes
            $header = array_map(function($col) {

                return '"' . str_replace('"', '""', $col) . '"';
            }, $columns);
            fwrite($file, implode(',', $header) . "\n");

            foreach ($paymentMethods as $paymentMethod) {
                $row = [];
                foreach ($columns as $column) {
                    $value = trim($paymentMethod[$column]);
                    $value = str_replace(["\r\n", "\r", "\n"], ' ', $value);
                    if ($column === 'id') {
                        // Ensure id is numeric and unquoted
                        $row[] = is_numeric($value) ? (int)$value : $value;
                    } else {
                        // Escape and quote other fields
                        $row[] = '"' . str_replace('"', '""', $value) . '"';
                    }
                }
                fwrite($file, implode(',', $row) . "\n");
            }

            fclose($file);
        };
        return $callback;
    }
}
