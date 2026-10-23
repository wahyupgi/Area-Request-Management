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
            'field_values.meta' => ['nullable', 'array'],
            'field_values.meta.*' => ['nullable', 'string', 'max:500'],
            'field_values.items' => ['nullable', 'array', 'max:500'],
            'field_values.items.*' => ['nullable', 'array'],
            'field_values.items.*.*' => ['nullable', 'string', 'max:5000'],
            'field_values.pengantar' => ['nullable', 'string', 'max:10000'],
            'field_values.penutup' => ['nullable', 'string', 'max:10000'],
            'field_values.keterangan' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
