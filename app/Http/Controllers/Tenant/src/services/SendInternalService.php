<?php
namespace App\Http\Controllers\Tenant\src\services;

use GuzzleHttp\Client;



class SendInternalService
{

    private $client;

    public function __construct()
    {
        $this->client = new Client(['verify' => false]);
    }

    public function sendInternal($url, $data, $token){

        try {
            $response = $this->client->post($url, [
                'json' => $data,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => $token
                ]
            ]);

            return $response->getBody()->getContents();

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 500;
            $body = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();

            return response($body, $statusCode)
                ->header('Content-Type', 'application/json');
        }


    }



}
