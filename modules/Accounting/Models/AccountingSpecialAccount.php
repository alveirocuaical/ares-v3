<?php

namespace Modules\Accounting\Models;

use App\Models\Tenant\ModelTenant;
use Illuminate\Database\Eloquent\Model;

class AccountingSpecialAccount extends ModelTenant
{
    protected $table = 'accounting_special_accounts';
    // protected $primaryKey = 'discount_account';
    // public $incrementing = false;
    // protected $keyType = 'string';

    protected $fillable = [
        'discount_account',
    ];

    // Relación con la cuenta contable (opcional)
    public function discountChartAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'discount_account', 'code');
    }
}