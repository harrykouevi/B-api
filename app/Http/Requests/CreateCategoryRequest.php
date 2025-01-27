<?php
/*
 * File name: CreateCategoryRequest.php
 * Last modified: 2024.04.18 at 17:53:40
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;

class CreateCategoryRequest extends FormRequest
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
        return Category::$rules;
    }
}
