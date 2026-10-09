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
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->whereIn(
                    'id',
                    $this->user()->availableBranchesForDocuments()->pluck('branches.id')->all()
                ),
            ],
            'attachment' => ['nullable', 'file', 'max:10240'],
        ];
    }
}
