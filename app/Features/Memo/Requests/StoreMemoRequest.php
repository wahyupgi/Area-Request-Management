<?php

namespace App\Features\Memo\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMemoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:255', 'unique:memos,code'],
            'template_id' => ['required', 'exists:memo_templates,id'],
            'title' => ['required', 'string', 'max:255'],
            'field_values' => ['nullable', 'array'],
            'attachment' => ['nullable', 'file', 'max:10240'],
        ];
    }
}
