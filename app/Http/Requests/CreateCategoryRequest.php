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

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use InfyOm\Generator\Utils\ResponseUtil;

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

     /**
     * @return array
     */
    public function validationData(): array
    {
        if (!auth()->user()->hasRole('admin')) {
            $this->offsetUnset('accepted');
        }
        return parent::validationData();
    }

     /**
     * Handle a failed validation attempt.
     *
     * @param Validator $validator
     * @return void
     *
     * @throws ValidationException
     */
    protected function failedValidation(Validator $validator): void
    {
        if ($this->isJson()) {
            $errors = array_values($validator->errors()->getMessages());
            $errorsResponse = ResponseUtil::makeError($errors);
            throw new ValidationException($validator, response()->json($errorsResponse));
        } else {
            throw (new ValidationException($validator))
                ->errorBag($this->errorBag)
                ->redirectTo($this->getRedirectUrl());
        }

    }
}
