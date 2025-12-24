<?php

namespace App\Http\Controllers\Tenant\src\services;


use App\Http\Controllers\Tenant\src\Http\Resources\DocumentPosResource;
use App\Http\Controllers\Tenant\src\repositories\Contracts\DocumentPosRepositoryInterface;
use App\Http\Controllers\Tenant\src\repositories\Contracts\RemissionPaymentInterface;
use Illuminate\Support\Facades\Log;

class DocumentPosService
{

    protected $documentPosRepository;
    protected $itemService;
    protected $taxesService;
    protected $sendInternalService;
    protected $remissionPayment;

    public function __construct(
        DocumentPosRepositoryInterface $documentPosRepository,
        ItemService $itemService,
        TaxesService $taxesService,
        SendInternalService $sendInternalService,
        RemissionPaymentInterface $remissionPayment
    ) {
        $this->documentPosRepository = $documentPosRepository;
        $this->itemService = $itemService;
        $this->taxesService = $taxesService;
        $this->sendInternalService = $sendInternalService;
        $this->remissionPayment = $remissionPayment;
    }

    public function getDocumentPos($id)
    {

        $documentPos = $this->documentPosRepository->getDocumentPos($id);

        $total_paid = collect($documentPos->payments)->sum('payment');
        $total = $documentPos->total;
        $total_difference = round($total - $total_paid, 2);

        return [
            'number_full' => $documentPos->number_full,
            'total_paid' => $total_paid,
            'total' => $total,
            'total_difference' => $total_difference
        ];
    }

    public function getDocumentPosPayments($id)
    {
        $documentPos = $this->documentPosRepository->getDocumentPos($id);

        return $documentPos->payments;
    }

    public function addDocumentPosPayment($request)
    {
        return  $this->documentPosRepository->addPayment($request);
    }

    public function getDocumentPosBasicDetails(int $id)
    {
        return $this->documentPosRepository->getDocumentPosBasicDetails($id);
    }

    public function createDocumentPos($request)
    {
        $itemsFound = $this->itemService->findItemById(collect($request->items)->pluck('item_id')->toArray());

        $itemsCollect = collect($request->items);
        foreach ($itemsFound as $item) {
            $request_sale_price = $itemsCollect->firstWhere('item_id', $item->id)['sale_unit_price'];
            $request_sale_price_with_tax = $itemsCollect->firstWhere('item_id', $item->id)['sale_unit_price_with_tax'];
            $request_subtotal = $itemsCollect->firstWhere('item_id', $item->id)['subtotal'];
            $request_tax = $itemsCollect->firstWhere('item_id', $item->id)['tax'];
            $request_total = $itemsCollect->firstWhere('item_id', $item->id)['total'];
            $request_quantity = $itemsCollect->firstWhere('item_id', $item->id)['quantity'];
            $request_total_tax = $itemsCollect->firstWhere('item_id', $item->id)['total_tax'];
            $request_tax_retention = $itemsCollect->firstWhere('item_id', $item->id)['tax']['retention'] ?? 0;
            $request_tax_total = $itemsCollect->firstWhere('item_id', $item->id)['tax']['total'] ?? 0;

            $item->request_price = $request_sale_price;
            $item->request_price_with_tax = $request_sale_price_with_tax;
            $item->request_subtotal = $request_subtotal;
            $item->request_tax = $request_tax;
            $item->request_total = $request_total;
            $item->request_quantity = $request_quantity;
            $item->request_total_tax = $request_total_tax;
            $item->request_tax_retention = $request_tax_retention;
            $item->request_tax_total = $request_tax_total;
            //$item->tax->retention = $request_tax['retention'] ?? 0;
            //$item->tax->total = $request_tax['total'] ?? 0;

        }
        $request['itemsFounded'] = $itemsFound;


        $taxesFromDb = $this->taxesService->getAll();

        $requestTaxes = collect($request->taxes);

        $taxesFromDb = $taxesFromDb->map(function ($tax) use ($requestTaxes) {

            $taxArr = is_array($tax) ? $tax : (method_exists($tax, 'toArray') ? $tax->toArray() : (array)$tax);

            $id = $taxArr['id'] ?? null;
            $request_tax = $requestTaxes->firstWhere('id', $id);

            $taxArr['retention'] = $request_tax['retention'] ?? 0;
            $taxArr['total']     = $request_tax['total'] ?? 0;

            // Log::info('taxFromDb', [$taxArr]);

            return $taxArr;
        })->values();

        $request['taxes'] = $taxesFromDb;

        return new DocumentPosResource($request);
    }

    public function sendDocument($request)
    {

        $documentPosResource = $this->createDocumentPos($request);

        $data = $documentPosResource->toArray($request);

        $apiResource = $request->document_type_id === 'RM' ? 'co-remissions' : 'document-pos';

        $scheme = 'https'; //! changue this in local dev
        $host = $request->attributes->get('tenant_fqdn', $request->getHost());
        $url = $scheme . '://' . $host . "/api/{$apiResource}";
        $token = $request->header('Authorization');


        try {
            $sendInternal = $this->sendInternalService->sendInternal($url, $data, $token);


            // normalizar a array asociativo desde JSON o desde objeto
            if (is_string($sendInternal)) {
                $response = json_decode($sendInternal, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    \Illuminate\Support\Facades\Log::error('sendInternal returned invalid JSON', ['raw' => $sendInternal]);
                    throw new \RuntimeException('Invalid response from internal service');
                }
            } elseif (is_object($sendInternal)) {
                $response = json_decode(json_encode($sendInternal), true);
            } else {
                $response = (array) $sendInternal;
            }

            if (!isset($response['data']['id'])) {
                \Illuminate\Support\Facades\Log::error('sendInternal response missing data.id', ['response' => $response]);
                throw new \RuntimeException('No document id returned by internal service');
            }

            $documentId = $response['data']['id'];
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('sendDocument failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        if ($request->document_type_id === 'RM') {
            return $this->getDocumentRMBasicDetails($documentId);
        }

        return $this->getDocumentPosBasicDetails($documentId);
    }

    public function getDocumentRMBasicDetails(int $id)
    {
        $remission = $this->remissionPayment->getRemissionById($id);
        if (!$remission) {
            throw new \Exception('Remission not found');
            Log::error('Remission not found');
            return null;
        }
        return [
            'number' => $remission->number_full,
        ];
    }
}
