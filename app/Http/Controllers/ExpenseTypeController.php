<?php

namespace App\Http\Controllers;

use App\Models\ExpenseType;
use Illuminate\Validation\Rule;

class ExpenseTypeController extends PharmacyResourceController
{
    protected function modelClass(): string
    {
        return ExpenseType::class;
    }

    protected function validationRules(?string $id = null): array
    {
        return [
            'account_id' => [
                'required',
                'integer',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query
                    ->where('account_type', 'expense')
                    ->where('is_transactional', true)
                    ->where('is_active', true)),
            ],
            'type_name' => ['required', 'string', 'max:255', Rule::unique('expense_types', 'type_name')->ignore($id)],
            'expense_category' => ['sometimes', Rule::in(['operational', 'administrative', 'marketing', 'maintenance'])],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function relations(): array
    {
        return ['account'];
    }

    protected function searchable(): array
    {
        return ['type_name'];
    }
}
