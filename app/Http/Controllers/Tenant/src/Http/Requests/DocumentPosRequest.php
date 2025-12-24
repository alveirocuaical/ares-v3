<?php

namespace App\Http\Controllers\Tenant\src\Http\Requests;

use Illuminate\Http\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class DocumentPosRequest extends FormRequest
{

    public function authorize()
    {
        return true;
    }


    public function rules()
    {

        return [
            'customer_id'         => 'required|integer',
            'document_type_id'    => 'required|string|in:90,RM',
            'establishment_id'    => 'required|integer',
            'type_document_id'    => 'required|integer',
            'total_discount'      => ['required', 'numeric', 'min:0'],
            'total_tax'           => ['required', 'numeric', 'min:0'],
            'subtotal'            => ['required', 'numeric', 'min:0'],
            'total'               => ['required', 'numeric', 'min:0'],
            'sale'                => ['required', 'numeric', 'min:0'],

            'items'               => 'required|array|min:1',
            'items.*.item_id'     => 'required|integer',
            'items.*.quantity'    => ['required', 'numeric'],
            'items.*.tax'         => 'nullable|array',
            'items.*.tax.id'      => 'nullable|integer',
            'items.*.tax.retention' => 'nullable|numeric|min:0',
            'items.*.tax.total'   => 'nullable|numeric|min:0',
            'items.*.tax_id'      => 'nullable|integer',
            'items.*.total'       => ['required', 'numeric'],
            'items.*.total_tax'   => ['nullable', 'numeric'],
            'items.*.sale_unit_price_with_tax' => ['nullable', 'numeric'],
            'items.*.sale_unit_price' => ['nullable', 'numeric'],

            'taxes'               => 'required|array',
            'taxes.*.id'          => 'required|integer',
            'taxes.*.retention'   => 'nullable|numeric|min:0',
            'taxes.*.total'       => 'nullable|numeric|min:0',

            'payments'            => 'required|array|min:1',
            'payments.*.payment_method_id' => 'required',
            'payments.*.payment'  => ['required', 'numeric', 'min:0.01'],
        ];
    }

    /**
     * Response failed validation in JSON response
     */
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            new Response([
                'success' => false,
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY)
        );
    }


    public function messages()
    {
        return [
            // customer_id
            'customer_id.required' => 'El campo customer_id es obligatorio.',
            'customer_id.integer'  => 'El campo customer_id debe ser un número entero.',

            // document_type_id
            'document_type_id.required' => 'El campo document_type_id es obligatorio.',
            'document_type_id.string'   => 'El campo document_type_id debe ser una cadena.',
            'document_type_id.in'       => 'El campo document_type_id debe ser 90 o RM.',

            // establishment_id
            'establishment_id.required' => 'El campo establishment_id es obligatorio.',
            'establishment_id.integer'  => 'El campo establishment_id debe ser un número entero.',

            // type_document_id
            'type_document_id.required' => 'El campo type_document_id es obligatorio.',
            'type_document_id.integer'  => 'El campo type_document_id debe ser un número entero.',

            // totals general
            'total_discount.required' => 'El campo total_discount es obligatorio.',
            'total_discount.numeric'  => 'El campo total_discount debe ser numérico.',
            'total_discount.min'      => 'El campo total_discount no puede ser negativo.',

            'total_tax.required' => 'El campo total_tax es obligatorio.',
            'total_tax.numeric'  => 'El campo total_tax debe ser numérico.',
            'total_tax.min'      => 'El campo total_tax no puede ser negativo.',

            'subtotal.required' => 'El campo subtotal es obligatorio.',
            'subtotal.numeric'  => 'El campo subtotal debe ser numérico.',
            'subtotal.min'      => 'El campo subtotal no puede ser negativo.',

            'total.required' => 'El campo total es obligatorio.',
            'total.numeric'  => 'El campo total debe ser numérico.',
            'total.min'      => 'El campo total no puede ser negativo.',

            'sale.required' => 'El campo sale es obligatorio.',
            'sale.numeric'  => 'El campo sale debe ser numérico.',
            'sale.min'      => 'El campo sale no puede ser negativo.',

            // items
            'items.required' => 'El arreglo items es obligatorio y debe contener al menos una línea.',
            'items.array'    => 'El campo items debe ser un arreglo.',
            'items.min'      => 'Se requiere al menos un elemento en items.',

            'items.*.item_id.required' => 'Cada item debe incluir item_id.',
            'items.*.item_id.integer'  => 'items.*.item_id debe ser un número entero.',

            'items.*.quantity.required' => 'Cada item debe incluir quantity.',
            'items.*.quantity.numeric'  => 'items.*.quantity debe ser numérico.',
            'items.*.quantity.min'      => 'items.*.quantity debe ser mayor a 0.',

            'items.*.tax.array'    => 'items.*.tax debe ser un arreglo cuando se provee.',
            'items.*.tax.id.integer' => 'items.*.tax.id debe ser un número entero.',
            'items.*.tax.retention.numeric' => 'items.*.tax.retention debe ser numérico.',
            'items.*.tax.retention.min'     => 'items.*.tax.retention no puede ser negativo.',
            'items.*.tax.total.numeric' => 'items.*.tax.total debe ser numérico.',
            'items.*.tax.total.min'     => 'items.*.tax.total no puede ser negativo.',

            'items.*.tax_id.integer' => 'items.*.tax_id debe ser un número entero.',

            'items.*.total.required' => 'Cada item debe incluir total.',
            'items.*.total.numeric'  => 'items.*.total debe ser numérico.',

            'items.*.total_tax.numeric' => 'items.*.total_tax debe ser numérico.',

            'items.*.sale_unit_price_with_tax.numeric' => 'items.*.sale_unit_price_with_tax debe ser numérico.',
            'items.*.sale_unit_price.numeric' => 'items.*.sale_unit_price debe ser numérico.',

            // payments
            'payments.required' => 'El arreglo payments es obligatorio y debe contener al menos un pago.',
            'payments.array'    => 'El campo payments debe ser un arreglo.',
            'payments.min'      => 'Se requiere al menos un elemento en payments.',

            'payments.*.payment_method_type_id.required' => 'Cada pago debe incluir payment_method_type_id.',
            'payments.*.payment_method_type_id.string'   => 'payments.*.payment_method_type_id debe ser una cadena.',

            'payments.*.payment.required' => 'Cada pago debe incluir payment.',
            'payments.*.payment.numeric'  => 'payments.*.payment debe ser numérico.',
            'payments.*.payment.min'      => 'payments.*.payment debe ser mayor o igual a 0.01.',
        ];
    }
}
