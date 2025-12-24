<?php
namespace App\Http\Controllers\Tenant\src\services;

use App\Http\Controllers\Tenant\src\repositories\Contracts\RemissionPaymentInterface;
use App\Http\Controllers\Tenant\src\repositories\RemissionPaymentImpl;

class RemissionPaymentService {

    protected $remissionPaymentRepository;

    public function __construct(RemissionPaymentInterface $remissionPaymentRepository)
    {
        $this->remissionPaymentRepository = $remissionPaymentRepository;
    }

    public function store ($payments, $remissionId) {

        $creditTypes = ['08', '09', '11', '12'];

        foreach ($payments as $payment) {
            if(!in_array($payment['payment_method_type_id'], $creditTypes)) {
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
