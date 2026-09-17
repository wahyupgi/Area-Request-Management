<?php

namespace App\Features\Approval\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectMemoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'notes.required' => 'Catatan alasan penolakan wajib diisi.',
            'notes.min' => 'Catatan minimal 5 karakter.',
        ];
    }
}
