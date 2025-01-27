<?php /*
 * File name: UpdateEServiceRequest.php
 * Last modified: 2024.04.18 at 17:39:25
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */ /** @noinspection PhpParamsInspection */

namespace App\Http\Requests;

use App\Models\EService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use InfyOm\Generator\Utils\ResponseUtil;

class UpdateEServiceRequest extends FormRequest
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return EService::$rules;
    }
}
