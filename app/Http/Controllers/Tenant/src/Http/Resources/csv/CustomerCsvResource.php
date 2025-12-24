<?php
namespace App\Http\Controllers\Tenant\src\Http\Resources\csv;

class CustomerCsvResource
{
    public static function toCsv($paymentMethods)
    {
        $columns = [
        'id',
        'type',
        'identity_document_type_id',
        'number',
        'name',
        'trade_name',
        'country_id',
        'department_id',

        'address',
        'email',
        'telephone',
        'perception_agent',
        'type_obligation_id',

        'percentage_perception',
        'person_type_id',
        'comment',
        'enabled',
        'type_person_id',
        'type_regime_id',
        'city_id',
        'code',
        'dv',
        'contact_name',
        'contact_phone',
        'postal_code',
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
