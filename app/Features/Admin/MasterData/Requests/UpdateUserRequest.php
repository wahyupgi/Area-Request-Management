<?php

namespace App\Features\Admin\MasterData\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,' . $userId],
            'email' => ['required', 'email', 'unique:users,email,' . $userId],
            'password' => ['nullable', 'string', 'min:6'],
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
