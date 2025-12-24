<?php
namespace App\Http\Controllers\Tenant\src\repositories;


use App\Http\Controllers\Tenant\src\repositories\Contracts\PaymentMethodInterface;
use App\Models\Tenant\PaymentMethodType;

class PaymentMethodImpl implements PaymentMethodInterface
{
    private $model;

    public function __construct()
    {
        $this->model = new PaymentMethodType();
    }

    public function getAll()
    {
        return $this->model->all();
    }
}
