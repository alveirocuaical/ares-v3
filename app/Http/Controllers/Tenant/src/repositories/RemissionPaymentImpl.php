<?php

namespace App\Http\Controllers\Tenant\src\repositories;

use Modules\Sale\Models\{
    Remission,
    RemissionPayment
};
use Exception, Illuminate\Support\Facades\DB;
use App\Http\Controllers\Tenant\src\repositories\Contracts\RemissionPaymentInterface;
use Illuminate\Support\Facades\Log;
use Modules\Finance\Traits\FinanceTrait;

class RemissionPaymentImpl implements RemissionPaymentInterface
{

    use FinanceTrait;

    const REFERENCE = 'ABONO EN CAJA';

    public function store($payment)
    {
        $id = $payment['id'];

        DB::connection('tenant')->transaction(function () use ($id, $payment) {

            $record = RemissionPayment::firstOrNew(['id' => $id]);
            $record->fill($payment);
            $record->save();
            $this->createGlobalPayment($record, $payment);
        });

        return [
            'success' => true,
            'message' => ($id) ? 'Pago editado con éxito' : 'Pago registrado con éxito'
        ];
    }

    public function getRemissionPayments($cash)
    {
        $userId = $cash->user_id;
        $start = $cash->date_opening;
        $end = $cash->date_closed ? $cash->date_closed : $cash->date_opening;

        $remissionPayments =  Remission::where('user_id', $userId)
            ->with(['payments' => function ($query) use ($start, $end) {
                $query->where('payment_method_type_id', '01')
                    ->where('reference', self::REFERENCE)
                    ->whereBetween('date_of_payment', [$start, $end]);
            }])->get()->sum(function ($remission) {
                return $remission->payments->sum('payment');
            });

        /* Log::info("data",[
                'user_id' => $userId,
                'start' => $start,
                'end' => $end,
                'remissionPayments' => $remissionPayments

            ]); */
        return $remissionPayments;
    }

    public function getRemissionById($id)
    {
        return Remission::find($id);
    }
}
