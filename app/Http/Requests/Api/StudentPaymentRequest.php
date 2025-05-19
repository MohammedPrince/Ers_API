<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StudentPaymentRequest extends FormRequest
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
            'stud_id' => 'required|string|max:15',
            'amount' => 'required|numeric',
            'voucher' => 'required|string|max:50',
            'transcation_no' => 'required|string|max:50',
            'date' => 'required|date_format:Y-m-d',
        ];
    }

    public function messages()
    {
        return [
            'stud_id.required' => 'Student index required',
            'amount.required' => 'Amount is required',
            'voucher.required' => 'Voucher is required',
            'transcation_no.required' => 'Transcation number is required',
            'date.required' => 'Date is required',
        ];
    }
}
