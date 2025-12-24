<?php
namespace App\Http\Controllers\Tenant\src\services;

use App\Http\Controllers\Tenant\src\repositories\Contracts\IncomeRepositoryInterface;

class IncomeService {

    protected $incomeRepository;

    public function __construct(IncomeRepositoryInterface $incomeRepository)
    {
        $this->incomeRepository = $incomeRepository;
    }

    public function getUserTotalPayments($cash) {

        $sumTotalPayments = $this->incomeRepository->getTotalPayments($cash);

        return $sumTotalPayments;

    }

}
