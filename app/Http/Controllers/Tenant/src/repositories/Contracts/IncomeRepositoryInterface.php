<?php
namespace App\Http\Controllers\Tenant\src\repositories\Contracts;

interface IncomeRepositoryInterface {

    public function getTotalPayments($cash);

}
