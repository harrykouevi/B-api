<?php

namespace App\Http\Requests;

use App\Models\OptionTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use InfyOm\Generator\Utils\ResponseUtil;
use Illuminate\Contracts\Validation\Validator;


class UpdateOptionTemplateRequest extends FormRequest
{

    public function rules(): array
    {
        return OptionTemplate::$rules;
    }

    public function authorize(): bool
    {
        return true;
    }

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