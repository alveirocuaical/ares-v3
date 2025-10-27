<?php

namespace Modules\Accounting\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class JournalEntryDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'date' => optional($this->journalEntry)->date, // Si `journalEntry` tiene una fecha
            'chart_account_code' => optional($this->chartOfAccount)->code, // Asumiendo que en el modelo ChartOfAccount existe una columna `code`
            'chart_account_name' => optional($this->chartOfAccount)->name, // Asumiendo que en el modelo ChartOfAccount existe una columna `name`
            'debit' => $this->debit,
            'credit' => $this->credit,
            'third_party_name' => optional($this->thirdParty)->name,
            'origin_id' => optional($this->thirdParty)->origin_id,
            'third_party_type' => optional($this->thirdParty)->type,
            'third_party_id' => $this->getThirdPartyFrontendId(),
            'chart_of_account_id' => $this->chart_of_account_id,
            'bank_account_id' => $this->bank_account_id,                 // <-- Agrega esto
            'bank_account_name' => optional($this->bankAccount)->description,
            'bank_account_number' => optional($this->bankAccount)->number,
            'payment_method_name' => $this->payment_method_name,         // <-- Y esto
        ];
    }
    private function getThirdPartyFrontendId()
    {
        if (!$this->thirdParty) return null;
        switch ($this->thirdParty->type) {
            case 'customers':
            case 'suppliers':
                return 'person_' . $this->thirdParty->id;
            case 'employee':
                return 'worker_' . $this->thirdParty->id;
            case 'seller':
                return 'seller_' . $this->thirdParty->id;
            default:
                return $this->thirdParty->id;
        }
    }
}