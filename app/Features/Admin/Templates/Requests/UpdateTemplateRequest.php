<?php

namespace App\Features\Admin\Templates\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'document_defaults' => ['nullable', 'array'],
            'document_defaults.*' => ['nullable', 'string', 'max:255'],
            'field_schema' => ['required', 'array', 'min:1'],
            'field_schema.*.key' => ['required', 'string'],
            'field_schema.*.label' => ['required', 'string'],
            'field_schema.*.type' => ['required', 'in:text,textarea,number,date,select,table'],
            'field_schema.*.required' => ['required', 'boolean'],
        ];
    }
}
