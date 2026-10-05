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
        $user = $this->user();

        return [
            'code' => ['nullable', 'string', 'max:255'],
            'template_id' => ['nullable', 'exists:memo_templates,id'],
            'title' => ['required', 'string', 'max:255'],
            'branch_id' => [
                'nullable',
                'integer',
                Rule::exists('branches', 'id')->where(function ($query) use ($user) {
                    $query->where('kc_user_id', $user->id)
                        ->orWhere(function ($legacyQuery) use ($user) {
                            $legacyQuery->where('id', $user->branch_id)
                                ->where(fn ($kcQuery) => $kcQuery->whereNull('kc_user_id')->orWhere('kc_user_id', $user->id));
                        });
                }),
            ],
            'field_values' => ['nullable', 'array'],
            'attachment' => ['nullable', 'file', 'max:10240'],
        ];
    }
}
