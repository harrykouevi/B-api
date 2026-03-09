<?php

namespace App\Http\Requests;

use App\Models\Post;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InfyOm\Generator\Utils\ResponseUtil;

class CreatePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
        if (auth()->user()->hasAnyRole(['admin','salon owner'])) {
            return true;
        }
        return false;

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [...Post::$rules ,
        'e_service_id' => 'nullable|exists:e_services,id',  

        'media' => ['nullable', 'array'],
        'media.*' => [
            'required',
            function ($attribute, $value, $fail) {

                // Cas 1 : vrai fichier uploadé
                if ($value instanceof \Illuminate\Http\UploadedFile) {

                    $allowedMimes = [
                        'image/jpeg',
                        'image/png',
                        'image/gif',
                        'image/svg+xml',
                        'video/mp4',
                        'video/quicktime',
                        'video/x-msvideo',
                        'video/x-ms-wmv',
                        'video/webm',
                        'video/x-matroska',
                    ];

                    if (!in_array($value->getMimeType(), $allowedMimes)) {
                        $fail('Type de fichier non autorisé.');
                    }

                    if ($value->getSize() > 400480 * 1024) {
                        $fail('Fichier trop volumineux.');
                    }

                    return;
                }

                // Cas 2 : UUID (upload temporaire)
                if (is_string($value)) {
                    if (!Str::isUuid($value)) {
                        $fail('UUID invalide.');
                    }
                    return;
                }

                $fail('Format de fichier invalide.');
            },
        ],

        'target.*' => 'nullable|array',  
        'target.*.model' => 'required|string',  
        'target.*.model_id' => [
            'required',
            function ($attribute, $value, $fail) {
                $index = explode('.', $attribute)[1];
                $modelName = $this->input("target.$index.model");
                $modelClass = 'App\Models\\' . $modelName;

                if (!class_exists($modelClass)) {
                    $fail('Invalid target model.');
                    return;
                }

                if (!$modelClass::where('id', $value)->exists()) {
                    $fail('The selected target id does not exist.');
                }
            }
        ],]; 
    }
}
