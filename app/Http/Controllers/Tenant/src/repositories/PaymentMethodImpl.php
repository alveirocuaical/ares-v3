<?php
namespace App\Http\Controllers\Tenant\src\repositories;


use App\Models\Tenant\PaymentMethodType;
use Modules\Factcolombia1\Models\Tenant\PaymentMethod;
use App\Http\Controllers\Tenant\src\repositories\Contracts\PaymentMethodInterface;

class PaymentMethodImpl implements PaymentMethodInterface
{
    private $model;

    public function __construct()
    {
        $this->model = new PaymentMethod();
    }

    public function getAll()
    {
        $excludedIds = [
            75, 69, 72, 71, 73, 68, 54, 55, 56, 57, 58, 59,
            1, 2, 3, 4, 5, 6, 7, 8, 9, 11, 12, 15, 16, 17, 18, 19, 21, 22,
            23, 24, 25, 26, 27, 28, 29, 32, 33, 34, 35, 36, 37, 38, 39, 40,
            41, 43, 44, 48, 49, 50, 51, 52, 53, 60, 61, 62, 63, 64, 65, 66,
            67, 70, 74, 75, 76, 77, 78, 91, 92, 93, 94, 95, 96, 97, "ZZZ"
        ];
        $paymentMethods = PaymentMethod::whereNotIn('id', $excludedIds)
            ->orWhereNotIn('id', $excludedIds) // Por si 'ZZZ' está en la columna code
            //->orderBy('name', 'asc')
            ->get();

        return $paymentMethods;
    }
}
