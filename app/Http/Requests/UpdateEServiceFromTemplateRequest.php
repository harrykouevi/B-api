<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEServiceFromTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'salon_id' => 'required|integer|exists:salons,id',
            'template.template_id' => 'nullable|exists:service_templates,id',
            'template.price' => 'nullable|numeric|min:0|max:99999999.99',
            'template.discount_price' => 'nullable|numeric|min:0|max:99999999.99',
            'template.duration' => 'nullable|max:16',
            'template.featured' => 'nullable|boolean',
            'template.enable_booking' => 'nullable|boolean',
            'template.enable_at_salon' => 'nullable|boolean',
            'template.enable_at_customer_address' => 'nullable|boolean',
            'template.available' => 'nullable|boolean',
            'template.options' => 'nullable|array',
            'template.options.*.option_id' => 'required|exists:option_templates,id',
            'template.options.*.price' => 'required|numeric|min:0|max:99999999.99',
            'template.options.*.option_group_id' => 'nullable|exists:option_groups,id',
        ];
    }
}