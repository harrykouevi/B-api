<?php

namespace App\Http\Requests;

use App\Models\ServiceTemplate;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use InfyOm\Generator\Utils\ResponseUtil;

class UpdateServiceTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ServiceTemplate::$rules;
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

    protected function failedValidation(Validator $validator): void
    {
        if ($this->isJson()) {
            $errors = array_values($validator->errors()->getMessages());
            if (!empty($errors)) {
                $errorsResponse = ResponseUtil::makeError((string)$errors);
            }
            throw new ValidationException($validator, response()->json($errorsResponse));
        }

        throw (new ValidationException($validator))
            ->errorBag($this->errorBag)
            ->redirectTo($this->getRedirectUrl());

    }
}