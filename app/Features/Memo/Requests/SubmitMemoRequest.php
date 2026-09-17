<?php

namespace App\Features\Memo\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitMemoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'signature_image' => ['nullable', 'file', 'mimes:png,jpg,jpeg', 'max:2048'],
        ];
    }
}
