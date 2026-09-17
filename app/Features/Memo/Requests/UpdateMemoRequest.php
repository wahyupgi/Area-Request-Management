<?php

namespace App\Features\Memo\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMemoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:255', 'unique:memos,code,' . $this->route('memo')?->id],
            'title' => ['required', 'string', 'max:255'],
            'field_values' => ['nullable', 'array'],
        ];
    }
}
