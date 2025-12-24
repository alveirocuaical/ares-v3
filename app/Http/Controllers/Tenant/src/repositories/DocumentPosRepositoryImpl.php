<?php
namespace App\Http\Controllers\Tenant\src\repositories;

use App\Models\Tenant\DocumentPos;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Tenant\src\repositories\Contracts\DocumentPosRepositoryInterface;
use App\Models\Tenant\DocumentPosPayment;
use Illuminate\Support\Facades\Log;
use Modules\Finance\Traits\FinanceTrait;

class DocumentPosRepositoryImpl implements DocumentPosRepositoryInterface
{
    use FinanceTrait;

    public function getDocumentPos(int $id): DocumentPos
    {
        return DocumentPos::with('payments')->find($id);
    }

    public function addPayment(object $payment): void
    {

        DB::connection('tenant')->transaction(function () use ( $payment) {

            $record = DocumentPosPayment::firstOrNew(['id' => $payment->id]);
            $record->fill($payment->all());
            $record->save();
            $this->createGlobalPayment($record, $payment->all());
            return $record;
        });



    }

    public function getDocumentPosBasicDetails(int $id)
    {
        return DocumentPos::setEagerLoads([])
                            ->select('id', 'qr', 'cude', 'prefix', 'number')
                            ->find($id);
    }

}
