<?php

namespace App\Http\Controllers\Tenant\src\repositories\Contracts;


interface RemissionPaymentInterface
{
    public function store($request);

    public function getRemissionPayments($userId);

    public function getRemissionById($id);
}
