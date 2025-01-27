<?php
/*
 * File name: CreateCurrencyRequest.php
 * Last modified: 2024.04.18 at 17:53:40
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Requests;

use App\Models\Currency;
use Illuminate\Foundation\Http\FormRequest;

class CreateCurrencyRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
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
        return Currency::$rules;
    }
}
