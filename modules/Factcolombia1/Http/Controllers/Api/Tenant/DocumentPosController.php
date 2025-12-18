<?php

namespace Modules\Factcolombia1\Http\Controllers\Api\Tenant;


use App\Http\Controllers\Controller;
use App\Http\Controllers\Tenant\DocumentPosController as WebDocumentPosController;
use App\Http\Resources\Tenant\DocumentPosResource;
use App\Models\Tenant\DocumentPos;
use Illuminate\Http\Request;


class DocumentPosController extends Controller
{
    public function voided($id) {

        $webController = (new WebDocumentPosController)->anulate($id);
        $search = DocumentPos::find($id);
        $record = new DocumentPosResource($search);

        return [
            'success' => true,
            'data' => [
                'id' => $id,
                'state_type' => $search->state_type,
                'document' => $record
            ]
        ];
    }

    public function storeApi(Request $request)
    {
        // Instancia el controlador Tenant POS
        $tenantController = app(\App\Http\Controllers\Tenant\DocumentPosController::class);

        // Llama al método store del Tenant POS y recibe la respuesta
        $result = $tenantController->store($request);

        // Obtener el documento POS recién creado
        $document = DocumentPos::find($result['data']['id'] ?? null);

        // Construir respuesta personalizada
        $number = '';
        $filename = '';
        $print_ticket = null;
        if ($document) {
            // Esta es la URL que el frontend usará para obtener el PDF
            $print_ticket = "/co-documents-pos/print-ticket/{$document->id}";
        }

        return response()->json([
            'success' => $result['success'] ?? false,
            'data' => [
                'id' => $result['data']['id'] ?? null,
                'number' => $number,
                'print_ticket' => $print_ticket,
            ],
            'message' => $result['message'] ?? '',
            'document_id' => $result['data']['id'] ?? null,
            'pdf_filename' => $filename,
            // Puedes agregar más parámetros personalizados aquí si lo necesitas
        ]);
    }

    public function printTicket($id)
    {
        // 1. Obtener el documento POS
        $document = \App\Models\Tenant\DocumentPos::findOrFail($id);

        // 2. Llamar al método record del controller web para obtener la URL real
        $webController = app(\App\Http\Controllers\Tenant\DocumentPosController::class);
        $resource = $webController->record($document->id);
        $resourceArray = $resource->toArray(request());
        $print_ticket_url = $resourceArray['print_ticket'] ?? null;

        // 3. Extraer el external_id del documento y llamar a toPrint
        $external_id = $document->external_id;
        $format = 'ticket';

        // 4. Llamar a toPrint y devolver el PDF
        return $webController->toPrint($external_id, $format);
    }

    
}