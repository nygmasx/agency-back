<?php

namespace App\Http\Requests\Team;

use App\Enums\TeamRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InviteMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['nullable', 'email', 'max:255'],
            'role' => ['required', Rule::enum(TeamRole::class)->except(TeamRole::Owner)],
        ];
    }

    public function messages(): array
    {
        return [
            'role.Illuminate\Validation\Rules\Enum' => 'The role must be admin, member, or viewer.',
        ];
    }
}
