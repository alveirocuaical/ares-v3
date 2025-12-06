<?php

namespace Modules\Factcolombia1\Http\Controllers\Api\Tenant;

use Illuminate\Http\Request;
use Modules\Factcolombia1\Http\Controllers\Controller;
use Modules\Factcolombia1\Http\Controllers\Tenant\DocumentController as WebDocumentController;
use Modules\Factcolombia1\Http\Resources\Tenant\DocumentCollection;
use Modules\Factcolombia1\Http\Requests\Tenant\DocumentRequest;
use Modules\Factcolombia1\Http\Resources\Tenant\PersonCollection;
use Modules\Factcolombia1\Http\Resources\Tenant\ItemApiCollection;
// use Modules\Factcolombia1\Models\Tenant\Document;
use App\Models\Tenant\Document;
use App\Models\Tenant\Person;
use App\Models\Tenant\Item;
use Modules\Factcolombia1\Models\Tenant\Tax;

use DB;
use Modules\Document\Traits\SearchTrait;
use Modules\Factcolombia1\Helpers\DocumentHelper;
use Modules\Factcolombia1\Traits\Tenant\DocumentTrait;
use Modules\Factcolombia1\Models\Tenant\Company;
use Modules\Factcolombia1\Models\Tenant\TypeDocument;
use Modules\Factcolombia1\Models\TenantService\Company as ServiceTenantCompany;
use Facades\Modules\Factcolombia1\Models\Tenant\Document as FacadeDocument;

class DocumentController extends Controller
{
    use DocumentTrait, SearchTrait;

    const REGISTERED = 1;
    const ACCEPTED = 5;
    const REJECTED = 6;

    public function tables()
    {
        return (new WebDocumentController)->tables();
    }

    public function store(DocumentRequest $request)
    {
        // dd($request->all());
        //\Log::info('Hasta aqui empieza');
        // $invoice = $request->all();
        // return (new WebDocumentController)->store($request, json_encode($request->service_invoice));

        // copia controller
        DB::connection('tenant')->beginTransaction();
        try {
            // validacion de tenant
            $this->company = Company::query()->with('country', 'version_ubl', 'type_identity_document')->firstOrFail();
            // if (($this->company->limit_documents != 0) && (Document::count() >= $this->company->limit_documents)) {
            //     return [
            //         'success' => false,
            //         'message' => '"Has excedido el límite de documentos de tu cuenta."'
            //     ];
            // }
            $response =  null;
            $response_status =  null;
            $ignore_state_document_id = true;
            $base_url = config('tenant.service_fact');
            $id_test = $this->company->test_id;
            $serviceCompany = ServiceTenantCompany::first();
            $token = $serviceCompany->api_token;

            $service_invoice = $this->generateServiceInvoice($request);

            if($this->company->type_environment_id == 2 && $this->company->test_id != 'no_test_set_id') {
                $ch = curl_init("{$base_url}ubl2.1/invoice/{$id_test}");
            } else {
                $ch = curl_init("{$base_url}ubl2.1/invoice");
            }

            $data_document = json_encode($service_invoice);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS,($data_document));
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Accept: application/json',
                "Authorization: Bearer {$token}"
            ));
            $response = curl_exec($ch);
            $response_status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $response_model = json_decode($response);
            $zip_key = null;
            $invoice_status_api = null;

            if(isset($response_model->errors)) {
                return [
                    'success' => false,
                    'message' => $response_model->message,
                    'errors' => $response_model->errors
                ];
            }

            if($response_status_code != 200) {
                return [
                    'success' => false,
                    'message' => $response_model->message,
                    'line' => $response_model->line,
                    'trace' => $response_model->trace[0]
                ];
            }
            //\Log::info('Después de la validación de errores y código de estado');

            if($serviceCompany->type_environment_id == 2 && $serviceCompany->test_id != 'no_test_set_id'){
                if(array_key_exists('urlinvoicepdf', $response_model) && array_key_exists('urlinvoicexml', $response_model))
                {
                    if(!is_string($response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ZipKey))
                    {
                        if(is_string($response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ErrorMessageList->XmlParamsResponseTrackId->Success))
                        {
                            if($response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ErrorMessageList->XmlParamsResponseTrackId->Success == 'false')
                            {
                                return [
                                    'success' => false,
                                    'message' => $response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ErrorMessageList->XmlParamsResponseTrackId->ProcessedMessage
                                ];
                            }
                        }
                    }
                    else
                        if(is_string($response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ZipKey))
                        {
                            $zip_key = $response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ZipKey;
                        }
                }

                $response_status = null;

            }
            else{
                $correlative_api = $service_invoice['number'];
                if($response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->IsValid == "true")
                    $this->setStateDocument(1, $correlative_api);
                else
                {
                    if(is_array($response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->ErrorMessage->string))
                        $mensajeerror = implode(",", $response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->ErrorMessage->string);
                    else
                        $mensajeerror = $response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->ErrorMessage->string;
                }
            }
            //\Log::info('aqui empieza la creación del documento en el sistema');

            // 1. Obtener el cliente o crearlo si no existe
            $customer_data = $request->customer ?? $request->service_invoice['customer'];
            $person = Person::where('number', $customer_data['identification_number'])->first();
            if (!$person) {
                $person = new Person();
                $person->type = 'customers';
                $person->number = $customer_data['identification_number'];
                $person->name = $customer_data['name'];
                $person->save();
            }
            $resolution = TypeDocument::where('resolution_number', $service_invoice['resolution_number'])->where('prefix', $service_invoice['prefix'])->orderBy('resolution_date', 'desc')->get();

            $newRequest = new Request();
            $newRequest->type_document_id = $resolution[0]->id;
            $newRequest->resolution_id = $resolution[0]->id;
            $newRequest->type_invoice_id = $resolution[0]->code;
            $newRequest->customer_id = $person->id;
            $newRequest->currency_id = 170;
            $newRequest->date_issue = $request->date_issue ?? $request->service_invoice['date'];
            $newRequest->date_expiration = $service_invoice['payment_form']['payment_due_date'];
            $newRequest->observation = $request->observation ?? $request->service_invoice['notes'] ?? '';
            $newRequest->sale = $request->sale ?? $request->service_invoice['legal_monetary_totals']['payable_amount'];
            $newRequest->total = $request->total ?? $request->service_invoice['legal_monetary_totals']['payable_amount'];
            $newRequest->total_discount = $request->total_discount ?? $request->service_invoice['legal_monetary_totals']['allowance_total_amount'] ?? 0;
            $newRequest->taxes = Tax::all();
            $newRequest->total_tax = $request->total_tax ?? $request->service_invoice['legal_monetary_totals']['tax_inclusive_amount'] - $request->service_invoice['legal_monetary_totals']['line_extension_amount'];
            $newRequest->subtotal = $request->subtotal ?? $request->service_invoice['legal_monetary_totals']['line_extension_amount'];
            $newRequest->payment_form_id = $request->payment_form_id ?? $request->service_invoice['payment_form']['payment_form_id'];
            $newRequest->payment_method_id = $request->payment_method_id ?? $request->service_invoice['payment_form']['payment_method_id'];
            $newRequest->time_days_credit = $request->time_days_credit ?? $request->service_invoice['payment_form']['duration_measure'];
            $newRequest->items = $request->items ?? $request->service_invoice['invoice_lines'];
            $newRequest->number = $service_invoice['number'];
            $newRequest->prefix = $service_invoice['prefix'];
            $newRequest->resolution_number = $service_invoice['resolution_number'];
            $newRequest->payments = $request->payments ?? [];

            if($response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->IsValid == 'true') {
                $state_document_id = self::ACCEPTED;
            } else {
                $state_document_id = self::REJECTED;
            }

            $newRequest->merge(['state_document_id' => $state_document_id]);
            // Si tienes otros campos, agrégalos aquí

            // 3. Crear el documento en el sistema
            $prefix = (object)['prefix' => $service_invoice['prefix']];
            //\Log::info('Datos del documento antes de guardar', $newRequest->toArray());
            $this->document = DocumentHelper::createDocument($newRequest, $prefix, $service_invoice['number'], $this->company, $response, $response_status, $serviceCompany->type_environment_id);
            (new DocumentHelper())->savePayments($this->document, $newRequest->payments, $newRequest); // no tiene payments el json
        }
        catch (\Exception $e) {

            \Log::error($e);
            // dd($e);
            DB::connection('tenant')->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
            ];
        }

        DB::connection('tenant')->commit();
        $this->company = Company::query()->with('country', 'version_ubl', 'type_identity_document')->firstOrFail();
        if (($this->company->limit_documents != 0) && (Document::count() >= $this->company->limit_documents - 10))
            $over = ", ADVERTENCIA, ha consumido ".Document::count()." documentos de su cantidad contratada de: ".$this->company->limit_documents;
        else
            $over = "";

        $document_helper = new DocumentHelper();
        if($response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->IsValid == 'true'){
            $document_helper->updateStateDocument(self::ACCEPTED, $this->document);
            return [
                'success' => true,
                'validation_errors' => false,
                'message' => "Se registro con éxito el documento #{$this->document->prefix}{$this->document->number}. {$over}",
                'data' => [
                    'id' => $this->document->id,
                    'document' => $this->document
                ]
            ];
        }
        else{
            $document_helper->updateStateDocument(self::REJECTED, $this->document);
            return [
                'success' => true,
                'validation_errors' => true,
                'message' => "Error al Validar Factura Nro: #{$this->document->prefix}{$this->document->number}., Sin embargo se guardo la factura para posterior envio, ... Errores: ".$mensajeerror." {$over}",
                'data' => [
                    'id' => $this->document->id,
                    'document' => $this->document
                ]
            ];
        }
    }

    public function searchItems(Request $request)
    {
        $records = Item::query()
            ->when($request->has('name'), function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->name . '%')
                    ->orWhere('description', 'like', '%' . $request->name . '%')
                    ->orwhere('internal_id', 'like', '%' .$request->name . '%');
            });

        return new ItemApiCollection($records->paginate(config('tenant.items_per_page')));
    }

    public function searchDocuments(Request $request)
    {
        $records = Document::query()
            ->when($request->has('serie'), function ($query) use ($request) {
                $query->where('prefix', 'like', '%' . $request->serie . '%');
            })
            ->when($request->has('number'), function ($query) use ($request) {
                $query->where('number', 'like', '%' . $request->number . '%');
            });

        return new DocumentCollection($records->paginate(config('tenant.items_per_page')));
    }

    public function searchCustomers(Request $request)
    {
        $records = Person::query()
            ->when($request->has('name'), function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->name . '%');
            })
            ->when($request->has('number'), function ($query) use ($request) {
                $query->where('number', 'like', '%' . $request->number . '%');
            });

        return new PersonCollection($records->paginate(config('tenant.items_per_page')));
    }

    public function getNextConsecutive($form)
    {
        $base_url = config('tenant.service_fact');
        $token = ServiceTenantCompany::first()->api_token;
        $data_document = json_encode($form);

        $ch = curl_init("{$base_url}ubl2.1/invoice/current_number/{$form['type_document_id']}/{$form['prefix']}/{$form['ignore_state']}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            "Authorization: Bearer {$token}"
        ));
        $response = curl_exec($ch);
        $response_status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $response_model = json_decode($response);
        /*
         * number is current - no next
            {
                "number": 991500982,
                "success": true,
                "prefix": "SETP"
            }
        */
        if($response_status_code == 200) {
            return $response_model->number;
        }
    }

    private function generateServiceInvoice($request)
    {
        //\Log::info('aqui empieza la limpieza');
        $service_invoice = $request->service_invoice;
        $company = ServiceTenantCompany::first();
        $to_get_number = [
            'type_document_id' => $service_invoice['type_document_id'],
            'prefix' => $service_invoice['prefix'],
            'ignore_state' => ($company->type_environment_id === 2)
        ];

        $service_invoice['number'] = $this->getNextConsecutive($to_get_number);
        $service_invoice['foot_note'] = "Modo de operación: Software Propio - by ".env('APP_NAME', 'FACTURADOR');
        $service_invoice['web_site'] = env('APP_NAME', 'FACTURADOR');
        $service_invoice['notes'] = $request->observation;
        $service_invoice['date'] = date('Y-m-d', strtotime($request->date_issue));
        $service_invoice['time'] = date('H:i:s');
        if ($request->has('payment_form_id')) {
            $service_invoice['payment_form']['payment_form_id'] = $request->payment_form_id;
        }
        if ($request->has('payment_method_id')) {
            $service_invoice['payment_form']['payment_method_id'] = $request->payment_method_id;
        }
        if ($request->has('time_days_credit')) {
            $service_invoice['payment_form']['duration_measure'] = $request->time_days_credit;
        }
        if ($request->has('payment_form_id')) {
            $service_invoice['payment_form']['payment_due_date'] = $request->payment_form_id == '1'
                ? date('Y-m-d')
                : date('Y-m-d', strtotime($request->date_expiration));
        }
        $service_invoice['ivaresponsable'] = $this->company->type_regime->name;
        $service_invoice['nombretipodocid'] = $this->company->type_identity_document->name;
        $service_invoice['tarifaica'] = $this->company->ica_rate;
        $service_invoice['actividadeconomica'] = $this->company->economic_activity_code;
        $service_invoice['customer']['dv'] = $this->validarDigVerifDIAN($service_invoice['customer']['identification_number']);

        // sucursal
        $sucursal = \App\Models\Tenant\Establishment::where('id', auth()->user()->establishment_id)->first();
        $service_invoice['establishment_name'] = $sucursal->description;
        if($sucursal->address != '-') {
            $service_invoice['establishment_address'] = $sucursal->address;
        }
        if($sucursal->telephone != '-') {
            $service_invoice['establishment_phone'] = $sucursal->telephone;
        }
        if(!is_null($sucursal->establishment_logo)) {
            if(file_exists(public_path('storage/uploads/logos/'.$sucursal->id."_".$sucursal->establishment_logo))){
                $establishment_logo = base64_encode(file_get_contents(public_path('storage/uploads/logos/'.$sucursal->id."_".$sucursal->establishment_logo)));
                $service_invoice['establishment_logo'] = $establishment_logo;
            }
        }
        if(!is_null($sucursal->email)) {
            $service_invoice['establishment_email'] = $sucursal->email;
        }
        // end sucursal

        if(!is_null($this->company->jpg_firma_facturas)){
            if(file_exists(public_path('storage/uploads/logos/'.$this->company->jpg_firma_facturas))){
                $firma_facturacion = base64_encode(file_get_contents(public_path('storage/uploads/logos/'.$this->company->jpg_firma_facturas)));
                $service_invoice['firma_facturacion'] = $firma_facturacion;
            }
        }

        // $datoscompany = Company::with('type_regime', 'type_identity_document')->firstOrFail(); // $this->company ya lo posee
        if(file_exists(storage_path('template.api'))){
            $service_invoice['invoice_template'] = "one";
            $service_invoice['template_token'] = password_hash($this->company->identification_number, PASSWORD_DEFAULT);
        }

        if(file_exists(storage_path('sendmail.api'))) {
            $service_invoice['sendmail'] = true;
        }

        return $service_invoice;
    }
    public function searchNameApi($nit, $document_type_id = 31)
    {
        $company = \Modules\Factcolombia1\Models\TenantService\Company::first();
        if ($company && $company->type_environment_id == 1) { // Producción
            $token = $company->api_token;
            $base_url = config('tenant.service_fact');
            $url = "{$base_url}customer/{$document_type_id}/{$nit}";

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // igual que otras consultas
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // igual que otras consultas
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Accept: application/json',
                "Authorization: Bearer {$token}"
            ));

            $response = curl_exec($ch);
            $curl_error = curl_error($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response === false) {
                \Log::error('Error al consultar la API de adquirente: '.$curl_error);
                return [
                    'error' => 'No se pudo conectar con el servicio de adquirentes.'
                ];
            }

            $data = json_decode($response);

            if ($http_code == 200 && isset($data->success) && $data->success === true && isset($data->ResponseDian->GetAcquirerResponse->GetAcquirerResult->StatusCode) && $data->ResponseDian->GetAcquirerResponse->GetAcquirerResult->StatusCode == "200") {
                $name = $data->ResponseDian->GetAcquirerResponse->GetAcquirerResult->ReceiverName ?? null;
                $email = $data->ResponseDian->GetAcquirerResponse->GetAcquirerResult->ReceiverEmail ?? null;
                return [
                    'data' => $name,
                    'email' => $email
                ];
            } else {
                $message = isset($data->message) ? $data->message : 'No se encontró el adquirente';
                \Log::error('API adquirente error: '.$message);
                return [
                    'error' => $message
                ];
            }
        }
        return [
            'error' => 'No está en ambiente de producción.'
        ];
    }

    public function setStateDocument($type_service, $DocumentNumber)
    {
        $company = ServiceTenantCompany::firstOrFail();
        $base_url = config('tenant.service_fact');
        $ch2 = curl_init("{$base_url}ubl2.1/invoice/state_document/{$type_service}/{$DocumentNumber}");

        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            "Authorization: Bearer {$company->api_token}"
        ));
        $response_data = curl_exec($ch2);
        $err = curl_error($ch2);
        curl_close($ch2);
        $response_encode = json_decode($response_data);
        if($err){
            return null;
        }
        else{
            return $response_encode;
        }
    }

    public function storeApi(DocumentRequest $request)
    {
        // Instancia el controlador Tenant
        $tenantController = app(\Modules\Factcolombia1\Http\Controllers\Tenant\DocumentController::class);

        // Llama al método store del Tenant y recibe la respuesta
        $result = $tenantController->store($request);

        // Obtener el documento recién creado
        $document = Document::find($result['data']['id'] ?? null);

        // Obtener el nombre del PDF desde response_api
        $pdf_filename = '';
        $qr_link = '';
        $qr_base64 = '';
        $number = '';
        $number_to_letter = '';
        if ($document && $document->response_api) {
            $api_response = json_decode($document->response_api);

            // 1. PDF
            // if (isset($api_response->urlinvoicepdf)) {
            //     $pdf_filename = $api_response->urlinvoicepdf;
            // }

            // 2. QR
            // if (isset($api_response->QRStr)) {
            //     $qr_link = $api_response->QRStr;

            //     // Generar QR como imagen base64 usando mpdf/qrcode
            //     $qrCode = new QrCode($qr_link);
            //     $output = new Output\Png();
            //     $qr_base64 = base64_encode($output->output($qrCode, 200, 200));
            //     // El frontend puede usar: 'data:image/png;base64,' + qr_base64
            // }
        }

        if ($document) {
            $number = $document->prefix . $document->number;
            // 4. Total en letras
            //$number_to_letter = $document->number_to_letter ?? '';
        }


        // Personaliza la respuesta según lo que necesites
        return response()->json([
            'success' => $result['success'] ?? false,
            'validation_errors' => $result['validation_errors'] ?? true,
            'data' => [
                'id' => $result['data']['id'] ?? null,
                'number' => $number,
                'number_to_letter' => $number_to_letter,
                'qr_link' => $qr_link,
                'qr_base64' => $qr_base64,
            ],
            'message' => $result['message'] ?? '',
            'document_id' => $result['data']['id'] ?? null,
            'pdf_filename' => $pdf_filename,
            // Puedes agregar más parámetros personalizados aquí
            'custom_param' => 'valor personalizado'
        ]);
    }

    public function downloadFile($filename)
    {
        $company = ServiceTenantCompany::firstOrFail();
        $base_url = config('tenant.service_fact');
        $ch = curl_init("{$base_url}ubl2.1/download/{$company->identification_number}/{$filename}/BASE64");

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer {$company->api_token}"
        ));
        $response_data = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if($err){
            return response()->json(['success' => false, 'message' => $err]);
        } else {
            return response()->json([
                'success' => true,
                'filebase64' => $response_data
            ]);
        }
    }
}