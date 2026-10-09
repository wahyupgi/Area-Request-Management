<?php

namespace App\Features\Admin\MasterData\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('areas', 'id')->whereNull('parent_id'),
                Rule::notIn([$this->route('area')?->id]),
            ],
            'kc_user_ids' => ['nullable', 'array'],
            'kc_user_ids.*' => ['integer', Rule::exists('users', 'id')->where('role', 'KC')],
            'existing_branches' => ['nullable', 'array'],
            'existing_branches.*.id' => ['required', 'integer', 'distinct'],
            'existing_branches.*.name' => ['required', 'string', 'max:255'],
            'branch_names' => ['nullable', 'array'],
            'branch_names.*' => ['string', 'max:255', 'distinct:ignore_case'],
        ];
    }
}
