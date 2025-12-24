<?php
namespace App\Http\Controllers\Tenant\src\repositories\Contracts;

interface TaxesRepositoryInterface
{
    public function getAll();

    public function getAllTaxeTypes();
}
