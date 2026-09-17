<?php

namespace App\Features\Signature\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSignatureSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'signature_schema' => ['nullable', 'array', 'max:6'],
            'signature_schema.*.name' => ['required', 'string', 'max:255'],
            'signature_schema.*.role' => ['required', 'string', 'max:100'],
            'signature_schema.*.location' => ['required', 'in:document,bottom_right'],
        ];
    }
}
