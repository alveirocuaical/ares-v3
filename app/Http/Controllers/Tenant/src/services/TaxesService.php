<?php
namespace App\Http\Controllers\Tenant\src\services;

use App\Http\Controllers\Tenant\src\Http\Resources\csv\TaxCsvReosurce;
use App\Http\Controllers\Tenant\src\Http\Resources\csv\TypesTaxCsvResource;
use Modules\Factcolombia1\Http\Resources\Tenant\TaxCollection;
use App\Http\Controllers\Tenant\src\repositories\Contracts\TaxesRepositoryInterface;


class TaxesService
{
    protected $taxesRepository;

    public function __construct( TaxesRepositoryInterface $taxesRepository)
    {
        $this->taxesRepository = $taxesRepository;
    }

    public function getAllTaxes()
    {
        $taxes =  $this->taxesRepository->getAll();
        return TaxCsvReosurce::toCsv($taxes->toArray(request()));
    }

    public function getAllTaxeTypes()
    {
        $typesTax = $this->taxesRepository->getAllTaxeTypes();
        return TypesTaxCsvResource::toCsv($typesTax->toArray(request()));
    }

    public function getAll()
    {
        return $this->taxesRepository->getAll();
    }
}
