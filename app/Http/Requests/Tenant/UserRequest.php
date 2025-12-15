<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => [
                'required'
            ],
            'email' => [
                'required'
            ],
            'type' => [
                'required'
            ],
            'allow_cash_reports' => [
                'boolean'
            ],
            'password' => [
                'min:6',
                'confirmed',
            ]
        ];
    }
}