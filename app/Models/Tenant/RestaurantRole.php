<?php

namespace App\Models\Tenant;

use App\Models\Tenant\ModelTenant;

class RestaurantRole extends ModelTenant
{
    protected $fillable = [
        'code',
        'name',
        'description',
    ];

    public function getCollectionData() {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description
        ];
    }
}
