<?php
namespace App\Http\Controllers\Tenant\src\services;

use App\Http\Controllers\Tenant\src\Http\Resources\csv\PaymentMethodCsvResource;
use App\Http\Controllers\Tenant\src\repositories\Contracts\PaymentMethodInterface;

class PaymentMethodService
{
    protected $paymentMethodRepository;

    public function __construct( PaymentMethodInterface $paymentMethodRepository)
    {
        $this->paymentMethodRepository = $paymentMethodRepository;
    }


    public function getAllPaymentMethods()
    {
        $paymentMethods = $this->paymentMethodRepository->getAll();

        return  PaymentMethodCsvResource::toCsv($paymentMethods->toArray(request()));
    }
}
