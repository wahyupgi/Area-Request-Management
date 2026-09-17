<?php

namespace App\Features\Approval\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSignersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'signers' => 'nullable|array',
            'signers.*.name' => 'nullable|string|max:255',
            'signers.*.role' => 'nullable|string|max:100',
            'signers.*.location' => 'nullable|string|in:document,bottom_right',
        ];
    }
}
