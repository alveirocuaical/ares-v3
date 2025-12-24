<?php
namespace App\Http\Controllers\Tenant\src\repositories;

use App\Http\Controllers\Tenant\src\repositories\Contracts\CustomerRepositoryInterface;

use App\Models\Tenant\Person;

class CustomerRespositoryImpl implements CustomerRepositoryInterface
{
    protected $model;
    public function __construct()
    {
        $this->model = new Person();
    }

    public function findByDocumentNumber(string $documentNumber)
    {
        return $this->model->where('number', $documentNumber)
                        ->where('type', 'customers')
                        ->first();
    }

    public function getAll()
    {
        return $this->model::all();

    }
}
