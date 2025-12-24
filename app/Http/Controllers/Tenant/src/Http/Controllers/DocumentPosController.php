<?php

namespace App\Http\Controllers\Tenant\src\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Tenant\src\services\ItemService;
use App\Http\Controllers\Tenant\src\services\DocumentPosService;
use App\Http\Controllers\Tenant\src\Http\Requests\DocumentPosRequest;
use App\Http\Controllers\Tenant\src\Http\Resources\DocumentPosResource;

use App\Http\Controllers\Tenant\src\Http\Requests\DocumentPosPaymentRequest;
use App\Http\Controllers\Tenant\src\Http\Resources\DocumentPosPaymentsCollection;

class DocumentPosController extends Controller
{
    protected $documentPosService;
    protected $itemService;

    public function __construct(DocumentPosService $documentPosService, ItemService $itemService)
    {
        $this->documentPosService = $documentPosService;
        $this->itemService = $itemService;
    }


    public function finDocumentPos(int $id)
    {
        $documentPosData = $this->documentPosService->getDocumentPos($id);

        return response()->json($documentPosData, JsonResponse::HTTP_OK);
    }

    public function getDocumentPosPayments(int $id)
    {
        $documentPosPayments = $this->documentPosService->getDocumentPosPayments($id);

        return response()->json(new DocumentPosPaymentsCollection($documentPosPayments), JsonResponse::HTTP_OK);
    }

    public function addPayment(DocumentPosPaymentRequest $request)
    {

        $this->documentPosService->addDocumentPosPayment($request);
        return [
            'success' => true,
            'message' => ($request->id)?'Pago editado con éxito':'Pago registrado con éxito'
        ];
    }

    public function getDocumentPosBasicDetails(int $id)
    {
        $documentPosBasicDetails = $this->documentPosService->getDocumentPosBasicDetails($id);

        if(!$documentPosBasicDetails) {
            return response()->json(['message' => 'Documento no encontrado'], JsonResponse::HTTP_NOT_FOUND);
        }

        return response()->json($documentPosBasicDetails, JsonResponse::HTTP_OK);
    }


    public function store(DocumentPosRequest $request)
    {
        $sendDocument = $this->documentPosService->sendDocument($request);

        return response()->json($sendDocument, JsonResponse::HTTP_OK);

    }




}
