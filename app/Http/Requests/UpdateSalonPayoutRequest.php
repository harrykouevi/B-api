<?php
/*
 * File name: UpdateSalonPayoutRequest.php
 * Last modified: 2024.04.18 at 17:21:42
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Requests;

use App\Models\SalonPayout;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSalonPayoutRequest extends FormRequest
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
        return SalonPayout::$rules;
    }
}
