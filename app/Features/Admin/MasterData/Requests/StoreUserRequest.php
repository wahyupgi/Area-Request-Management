<?php

namespace App\Features\Admin\MasterData\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'in:KC,AM,ADMIN'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'area_id' => [
                Rule::requiredIf(in_array($this->input('role'), ['AM', 'KC'], true)),
                'nullable',
                'integer',
                Rule::exists('areas', 'id')->where(fn ($query) => $this->input('role') === 'AM'
                    ? $query->whereNull('parent_id')
                    : $query->whereNotNull('parent_id')),
            ],
        ];
    }
}
