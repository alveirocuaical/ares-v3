<?php
namespace App\Http\Controllers\Tenant\src\repositories\Contracts;


interface CustomerRepositoryInterface
{
    public function findByDocumentNumber(string $documentNumber);

    public function getAll();
}
