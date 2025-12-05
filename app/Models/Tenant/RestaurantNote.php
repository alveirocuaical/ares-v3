<?php

namespace App\Models\Tenant;

use App\Models\Tenant\ModelTenant;

class RestaurantNote extends ModelTenant
{
    public $timestamps = false;

    protected $fillable = [
        'description',
    ];

   
}
