<?php
namespace App\Http\Controllers\Tenant\src\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Tenant\src\services\PaymentMethodService;

class PaymentMethodController extends Controller
{
    protected $paymentMethodService;

    public function __construct( PaymentMethodService $paymentMethodService)
    {
        $this->paymentMethodService = $paymentMethodService;
    }

    public function getAllPaymentMethods()
    {
        return response()->stream(
            $this->paymentMethodService->getAllPaymentMethods(),
            200,
            [
                "Content-Type" => "text/csv",
                "Content-Disposition" => "attachment; filename=payment_methods.csv",
            ]
        );
    }

}
