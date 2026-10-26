<?php

namespace App\Features\Memo\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:255'],
            'template_id' => [
                'nullable',
                Rule::exists('memo_templates', 'id')->where('type', 'memo'),
            ],
            'title' => ['required', 'string', 'max:255'],
            'field_values' => ['nullable', 'array'],
            'field_values.meta' => ['nullable', 'array'],
            'field_values.meta.*' => ['nullable', 'string', 'max:500'],
            'field_values.table_columns' => ['nullable', 'array', 'max:50'],
            'field_values.table_columns.*' => ['nullable', 'array'],
            'field_values.table_columns.*.key' => ['nullable', 'string', 'max:100'],
            'field_values.table_columns.*.label' => ['nullable', 'string', 'max:255'],
            'field_values.table_columns.*.type' => ['nullable', 'string', 'max:50'],
            'field_values.items' => ['nullable', 'array', 'max:500'],
            'field_values.items.*' => ['nullable', 'array'],
            'field_values.items.*.*' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (is_array($value)) {
                        if (count($value) > 500) {
                            $fail("{$attribute} tidak boleh lebih dari 500 baris.");
                        }
                        return;
                    }

                    if (!is_scalar($value)) {
                        $fail("{$attribute} tidak valid.");
                        return;
                    }

                    if (mb_strlen((string) $value) > 5000) {
                        $fail("{$attribute} tidak boleh lebih dari 5000 karakter.");
                    }
                },
            ],
            'field_values.items.*.*.*' => ['nullable', 'array'],
            'field_values.items.*.*.*.*' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (!is_scalar($value)) {
                        $fail("{$attribute} tidak valid.");
                        return;
                    }

                    if (mb_strlen((string) $value) > 5000) {
                        $fail("{$attribute} tidak boleh lebih dari 5000 karakter.");
                    }
                },
            ],
            'field_values.pengantar' => ['nullable', 'string', 'max:10000'],
            'field_values.penutup' => ['nullable', 'string', 'max:10000'],
            'field_values.keterangan' => ['nullable', 'string', 'max:10000'],
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->whereIn(
                    'id',
                    $this->user()->availableBranchesForDocuments()->pluck('branches.id')->all()
                ),
            ],
            'attachment' => ['nullable', 'file', 'mimes:doc,docx,pdf', 'max:10240'],
        ];
    }
}
