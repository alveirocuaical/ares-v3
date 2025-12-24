<?php
namespace App\Http\Controllers\Tenant\src\services;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request as Psr7Request;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Factcolombia1\Models\TenantService\{
    Company as ServiceCompany
};

class ApidianService
{
    protected $baseUrl;
    protected $client;

    public function __construct()
    {
        $this->baseUrl = config("tenant.service_fact", "");
        $this->client = new Client();
    }

    public function findCustomerByDocumentNumber($documentNumber, $documentType)
    {
        try{
            $company = ServiceCompany::firstOrFail();
            $apiToken = $company->api_token;
            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer {$apiToken}"
            ];

            $request = new Psr7Request('GET', "{$this->baseUrl}customer/{$documentType}/{$documentNumber}", $headers);

            $response = $this->client->sendAsync($request)->wait();
            if ($response->getStatusCode()== 200) {
                $customertData = json_decode($response->getBody()->getContents(), true);
                if ($customertData['success'] == false) {
                    //Log::alert("Customer not found in Apidian: " . $customertData['message']);
                    return null;
                }
                //Log::info('Customer fetched from Apidian(service) : ', ['customer' => $customertData]);
                return $customertData;
            } else {
                Log::alert("Failed to fetch customer from Apidian: HTTP " . $response->getBody()->getContents());
                return null;
            }
        } catch (\Exception $e) {
            Log::alert("Error fetching customer from Apidian: " . $e->getMessage());
            return null;
        }

    }
}
