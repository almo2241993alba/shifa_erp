<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Validation\Rule;

class AccountController extends PharmacyResourceController
{
    protected function modelClass(): string
    {
        return Account::class;
    }

    protected function validationRules(?string $id = null): array
    {
        return [
            'parent_id' => ['nullable', 'integer', 'exists:accounts,id', Rule::notIn($id === null ? [] : [$id])],
            'code' => ['required', 'string', 'max:255', Rule::unique('accounts', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'account_type' => ['required', Rule::in(['asset', 'liability', 'equity', 'revenue', 'expense'])],
            'is_transactional' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function relations(): array
    {
        return ['parent', 'children'];
    }

    protected function searchable(): array
    {
        return ['code', 'name'];
    }
}
