<?php
namespace App\Http\Controllers\Tenant\src\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class DocumentPosResource extends JsonResource
{
    public function toArray($request)
    {

        return [
            'customer_id' => $this->customer_id,
            'document_type_id' => $this->document_type_id,
            'series_id' => null,
            'establishment_id' => $this->establishment_id,
            'type_document_id' => $this->type_document_id,
            'currency_id' => 170,
            'date_issue' => now('America/Bogota')->toDateString(),
            'date_of_issue' => now('America/Bogota')->toDateString(),
            'time_of_issue' => now('America/Bogota')->toTimeString(),
            'exchange_rate_sale' => 0,
            'date_expiration' => null,
            'type_invoice_id' => 1,
            'total_discount' => $this->total_discount,
            'total_tax' => $this->total_tax,
            'watch' => false,
            'subtotal' => $this->subtotal,
            'items' => ItemResource::collection($this->itemsFounded)->toArray($request),
            'taxes' => $this->taxes->map(function ($tax) use ($request) {

                return [
                    'id'            => $tax['id'],
                    'name'          => $tax['name'],
                    'code'          => $tax['code'],
                    'rate'          => $tax['rate'],
                    'conversion'    => $tax['conversion'],
                    'is_percentage' => $tax['is_percentage'],
                    'is_fixed_value'=> $tax['is_fixed_value'],
                    'is_retention'  => $tax['is_retention'],
                    'in_base'       => $tax['in_base'],
                    'in_tax'        => $tax['in_tax'],
                    'type_tax_id'   => $tax['type_tax_id'],
                    'type_tax'      => $tax['type_tax']->toArray($request),
                    'retention'     => isset($tax['retention']) ? $tax['retention'] : 0,
                    'total'         => isset($tax['total']) ? $tax['total'] : 0,


                ];
            })->toArray($request),
            'total' => $this->total,
            'sale' => $this->sale,
            'time_days_credit' => 0,
            'service_invoice' =>  (object)[],
            'payment_form_id' => 1,
            'payment_method_id' => 1,
            'payments' => collect($this->payments)->map(function($payment) {
                return [
                    'payment_method_type_id' => $payment['payment_method_type_id'],
                    'payment' => $payment['payment'],
                    'date_of_payment' => now('America/Bogota')->toDateString(),
                    'payment_destination_id' => 'cash',
                ];
            })->toArray(),
            'electronic' => false,
            'allowance_charges' => [],
            'prefix' => $this->document_type_id,



        ];
    }
}
