<?php
namespace App\Http\Controllers\Tenant\src\repositories;

use App\Http\Controllers\Tenant\src\repositories\Contracts\IncomeRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Modules\Finance\Models\Income;

class IncomeRepositoryImpl implements IncomeRepositoryInterface {

    const REFERENCE = 'ABONO EN CAJA';

    public function getTotalPayments($cash) {

        $userId = $cash->user_id;
        $start = $cash->date_opening ;
        $end = $cash->date_closed ? $cash->date_closed : $cash->date_opening;

        $totalPayments = Income::where('user_id', $userId)
                        ->with(['payments' => function($query) use ($start, $end) {
                            $query->where('payment_method_type_id', '01')
                            ->where('reference', self::REFERENCE)
                            ->whereBetween('date_of_payment', [$start, $end]);
        }])->get()->sum(function($income) {
            return $income->payments->sum('payment');
        });

        Log::info("data income",[
            'user_id' => $userId,
            'start' => $start,
            'end' => $end,
            'totalPayments' => $totalPayments

        ]);

        return $totalPayments;
    }

}
