<?php
namespace App\Http\Controllers\Tenant\src\Http\Resources\csv;

class PaymentMethodCsvResource
{
    public static function toCsv($paymentMethods)
    {
        $columns = [
            'id',
            'description',
            'has_card',
            'charge',
            'number_days',
            'show_ecommerce',
        ];

        $callback = function() use ($paymentMethods, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($paymentMethods as $paymentMethod) {
                $row = [];
                foreach ($columns as $column) {
                    $row[] = $paymentMethod[$column];
                }
                fputcsv($file, $row);
            }

            fclose($file);
        };
        return $callback;
    }
}
