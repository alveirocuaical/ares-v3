<?php
namespace App\Http\Controllers\Tenant\src\services;

use App\Http\Controllers\Tenant\src\repositories\Contracts\RemissionPaymentInterface;
use App\Http\Controllers\Tenant\src\repositories\RemissionPaymentImpl;
use Illuminate\Support\Facades\Log;

class RemissionPaymentService {

    protected $remissionPaymentRepository;

    public function __construct(RemissionPaymentInterface $remissionPaymentRepository)
    {
        $this->remissionPaymentRepository = $remissionPaymentRepository;
    }

    public function store ($payments, $remissionId) {

        $creditTypes = [13];

        //Log::info('Remission Payments: '.json_encode($payments));

        foreach ($payments as $payment) {
            Log::info('Remission Payment Method ID: '.$payment['payment_method_id']);
            if(!in_array($payment['payment_method_id'], $creditTypes)) {
                Log::info('Storing Remission Payment');
                $payment['id'] = null;
                $payment['remission_id'] = $remissionId;
                $this->remissionPaymentRepository->store($payment);
            };
        }

    }

    public function getUserRemissionPayments($cash) {

        $sumRemissionPayments = $this->remissionPaymentRepository->getRemissionPayments($cash);
        return $sumRemissionPayments;

    }

}
