<?php

namespace Modules\Accounting\Models;

use App\Models\Tenant\ModelTenant;

class BankReconciliationDetail extends ModelTenant
{
    protected $fillable = [
        'bank_reconciliation_id',
        'journal_entry_detail_id',
        'tipo',
        'fecha',
        'nombre_tercero',
        'origen',
        'n_soporte',
        'cheque',
        'concepto',
        'valor',
    ];

    public function bankReconciliation()
    {
        return $this->belongsTo(BankReconciliation::class);
    }

    public function journalEntryDetail()
    {
        return $this->belongsTo(JournalEntryDetail::class);
    }
}