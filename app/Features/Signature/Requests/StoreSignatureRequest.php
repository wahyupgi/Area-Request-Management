<?php

namespace App\Features\Signature\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSignatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'signature_image' => ['required', 'file', 'mimes:png,jpg,jpeg', 'max:2048'],
            'certificate_no' => ['nullable', 'string', 'max:100'],
        ];
    }
}
