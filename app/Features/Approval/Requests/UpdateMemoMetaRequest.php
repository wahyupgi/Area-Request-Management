<?php

namespace App\Features\Approval\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMemoMetaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:255'],
            'direktorat' => ['nullable', 'string', 'max:255'],
            'divisi' => ['nullable', 'string', 'max:255'],
            'perihal' => ['nullable', 'string', 'max:500'],
            'lampiran' => ['nullable', 'string', 'max:255'],
        ];
    }
}
