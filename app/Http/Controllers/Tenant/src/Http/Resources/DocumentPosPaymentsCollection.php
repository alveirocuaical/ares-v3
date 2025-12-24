<?php
namespace App\Http\Controllers\Tenant\src\Http\Resources;


use Illuminate\Http\Resources\Json\ResourceCollection;

class DocumentPosPaymentsCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function ($row) {
                return [
                    'id' => $row->id,
                    'document_pos_id' => $row->document_pos_id,
                    'date_of_payment' => $row->date_of_payment->format('d/m/Y'),
                    'payment_method_type_id' => $row->payment_method_type_id,
                    'payment_method_type_description' => optional($row->payment_method_type)->description,
                    'destination_description' => optional($row->global_payment)->destination_description,
                    'reference' => $row->reference,
                    'change' => $row->change,
                    'payment' => $row->payment,
                ];
            })
        ];
    }
}

