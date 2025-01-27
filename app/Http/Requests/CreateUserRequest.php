<?php
/*
 * File name: CreateUserRequest.php
 * Last modified: 2024.04.18 at 17:53:44
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
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
        User::$rules['password'] = 'required|string|min:6';
        User::$rules['email'] = 'required|string|email|max:255|unique:users';
        return User::$rules;
    }
}
