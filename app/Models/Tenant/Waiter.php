<?php

namespace App\Models\Tenant;

use App\Models\Tenant\ModelTenant;

class Waiter extends ModelTenant
{
    protected $fillable = [
        'name',
        'last_name',
    ];
}
