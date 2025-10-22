<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use InfyOm\Generator\Utils\ResponseUtil;




class CreateEServiceFromTemplateRequest extends FormRequest
{
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

    public function rules(): array
    {
        return [
            'salon_id' => 'required|integer|exists:salons,id',
            // 'template.template_id' => 'required|exists:service_templates,id',
            // 'template.price' => 'required|numeric|min:0|max:99999999.99',
            // 'template.discount_price' => 'nullable|numeric|min:0|max:99999999.99',
            // 'template.duration' => 'nullable|max:16',
            // 'template.featured' => 'nullable|boolean',
            // 'template.enable_booking' => 'nullable|boolean',
            // 'template.enable_at_salon' => 'nullable|boolean',
            // 'template.enable_at_customer_address' => 'nullable|boolean',
            // 'template.available' => 'nullable|boolean',
            // 'template.options' => 'nullable|array',
            // 'template.options.*.option_id' => 'required|exists:option_templates,id',
            // 'template.options.*.price' => 'required|numeric|min:0|max:99999999.99',
            // 'template.options.*.option_group_id' => 'nullable|exists:option_groups,id',
            

            'template_id' => 'required|exists:service_templates,id',
            'price' => 'required|numeric|min:0|max:99999999.99',
            'discount_price' => 'nullable|numeric|min:0|max:99999999.99',
            'duration' => 'nullable|max:16',
            'featured' => 'nullable|boolean',
            'enable_booking' => 'nullable|boolean',
            'enable_at_salon' => 'nullable|boolean',
            'enable_at_customer_address' => 'nullable|boolean',
            'available' => 'nullable|boolean',
            'options' => 'nullable|array',
            'options.*.option_id' => 'required|exists:option_templates,id',
            'options.*.price' => 'required|numeric|min:0|max:99999999.99',
            'options.*.option_group_id' => 'nullable|exists:option_groups,id',
       
        ];
    }
}