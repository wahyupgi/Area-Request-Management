<?php

namespace App\Features\Approval\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemoMetaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $memoId = $this->route('memo')?->id;

        return [
            'code' => ['nullable', 'string', 'max:255', Rule::unique('memos', 'code')->ignore($memoId)],
            'direktorat' => ['nullable', 'string', 'max:255'],
            'divisi' => ['nullable', 'string', 'max:255'],
            'kepada' => ['nullable', 'string', 'max:255'],
            'kepada_jabatan' => ['nullable', 'string', 'max:255'],
            'perihal' => ['nullable', 'string', 'max:500'],
            'lampiran' => ['nullable', 'string', 'max:255'],
        ];
    }
}
