<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class BankAccessRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'bank_name' => 'required|string',
            'bank_password' => 'required',
            'bank_ip' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'bank_name.required' => 'Enter bank name',
            'bank_password.required' => 'Enter bank password',
            'bank_ip.required' => 'Enter bank IP',

        ];
    }
}
