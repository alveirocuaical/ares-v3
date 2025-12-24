<?php
namespace App\Http\Controllers\Tenant\src\services;

use App\Http\Controllers\Tenant\src\repositories\Contracts\CustomerRepositoryInterface;

class CustomerService
{
    protected $customerRepository;

    public function __construct(CustomerRepositoryInterface $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    public function findCustomerByDocumentNumber($documentNumber)
    {
        return $this->customerRepository->findByDocumentNumber($documentNumber);
    }


    public function getAllCustomers(){
        return $this->customerRepository->getAll();
    }
}
