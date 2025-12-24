<?php
namespace App\Http\Controllers\Tenant\src\Http\Requests;



use Illuminate\Http\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


class DocumentPosPaymentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {

        //$id = $this->input('id');

        return [
            'date_of_payment' => [
                'date',
                'required',
            ],
            'payment_method_type_id' => [
                'required',
            ],
            'payment_destination_id' => [
                'required',
            ],
            'payment' => [
                'required',
            ],
            'document_pos_id' => [
                'required',
            ],
        ];

    }

    /**
    * Response failed validation in JSON response
    */
    public function failedValidation(Validator $validator) {
        throw new HttpResponseException(
            new Response([
                'success' => false,
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY)
        );
    }


    /**
     * Get messages from validations
     */
    public function messages()
    {
        return [
            'date_of_payment.required' => 'La fecha de pago es obligatoria',
            'date_of_payment.date' => 'La fecha de pago no tiene el formato correcto',
            'payment_method_type_id.required' => 'El método de pago es obligatorio',
            'payment_destination_id.required' => 'El destino de pago es obligatorio',
            'payment.required' => 'El monto de pago es obligatorio',
            'document_pos_id.required' => 'El id del documento POS es obligatorio',

        ];
    }

}
