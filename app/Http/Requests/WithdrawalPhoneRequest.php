<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WithdrawalPhoneRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'phone_number' => 'required|string|regex:/^\+?[1-9]\d{1,14}$/|unique:withdrawal_phones,phone_number,NULL,id,user_id,' . $this->user()->id,
            'user_id' => 'required|exists:users,id',
        ];
    }

    public function messages()
    {
        return [
            'phone_number.required' => 'A phone number is required.',
            'phone_number.regex' => 'The phone number format is invalid.',
            'phone_number.unique' => 'This phone number is already associated with your account.',
            'user_id.required' => 'A user ID is required.',
            'user_id.exists' => 'The selected user ID is invalid.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'user_id' => $this->user()->id,
        ]);
    }
}