<?php

namespace App\Http\Controllers\Tenant\src\Http\Controllers;


use App\Http\Controllers\Controller;
use App\Http\Controllers\Tenant\src\Http\Resources\csv\CustomerCsvResource;
use App\Http\Controllers\Tenant\src\services\ApidianService;
use App\Http\Controllers\Tenant\src\services\CustomerService;
use App\Http\Controllers\Tenant\src\Http\Resources\CustomerResource;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{

    protected $apidianService, $customerService;

    public function __construct(ApidianService $apidianService, CustomerService $customerService)
    {
        $this->apidianService = $apidianService;
        $this->customerService = $customerService;
    }


    public function getCustomerByDocument($documentType, $documentNumber)
    {
        $customer = $this->apidianService->findCustomerByDocumentNumber($documentNumber, $documentType);
        //Log::info('Customer fetched from Apidian(controller) : ', ['customer' => $customer]);
        if ($customer) {
            $customerInternal = $this->customerService->findCustomerByDocumentNumber($documentNumber);
            if ($customerInternal) {
                $customer['customer_id'] = $customerInternal->id;
            }

            return response()->json(['data' => new CustomerResource($customer)], 200);
        }

        return response()->json(['message' => 'Customer not found'], 404);
    }


    public function getAllCustomersCsv(){

        $customers = $this->customerService->getAllCustomers();

        return response()->stream(
            CustomerCsvResource::toCsv($customers->toArray(request())),
            200,
            [
                "Content-Type" => "text/csv",
                "Content-Disposition" => "attachment; filename=payment_methods.csv",
            ]
        );


    }

}
