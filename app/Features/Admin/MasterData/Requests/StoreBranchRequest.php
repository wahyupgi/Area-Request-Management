<?php

namespace App\Features\Admin\MasterData\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'area_id' => [
                'required',
                'integer',
                Rule::exists('areas', 'id')->where(fn ($query) => $query->whereNotNull('parent_id')),
            ],
            'kc_user_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
