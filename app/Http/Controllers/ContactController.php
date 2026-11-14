<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactController extends PharmacyResourceController
{
    protected function modelClass(): string
    {
        return Contact::class;
    }

    protected function validationRules(?string $id = null): array
    {
        $user = request()->user();
        $canManageFinancialFields = $user->hasAnyRole(['admin', 'accountant']);
        $canManageEmployeeFlag = $user->hasAnyRole(['admin', 'accountant']);

        return [
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where(function ($query) use ($user): void {
                    if ($user->branch_id !== null) {
                        $query->where('id', $user->branch_id);
                    } elseif ($user->role !== 'admin') {
                        $query->whereRaw('1 = 0');
                    }
                }),
            ],
            'account_id' => $canManageFinancialFields ? ['nullable', 'integer', 'exists:accounts,id'] : ['prohibited'],
            'customer_account_id' => $canManageFinancialFields ? ['nullable', 'integer', 'exists:accounts,id'] : ['prohibited'],
            'supplier_account_id' => $canManageFinancialFields ? ['nullable', 'integer', 'exists:accounts,id'] : ['prohibited'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:20', Rule::unique('contacts', 'tax_number')->ignore($id)],
            'contact_type' => ['sometimes', Rule::in(['B2C', 'B2B'])],
            'is_customer' => ['sometimes', 'boolean'],
            'is_supplier' => $canManageFinancialFields ? ['sometimes', 'boolean'] : ['prohibited'],
            'is_employee' => $canManageEmployeeFlag ? ['sometimes', 'boolean'] : ['prohibited'],
            'is_insurance_provider' => $canManageFinancialFields ? ['sometimes', 'boolean'] : ['prohibited'],
            'credit_limit' => $canManageFinancialFields ? ['sometimes', 'numeric', 'min:0'] : ['prohibited'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function applyScope(Builder $query, Request $request): void
    {
        if ($request->user()->branch_id !== null) {
            $query->where('branch_id', $request->user()->branch_id);
        } elseif ($request->user()->role !== 'admin') {
            $query->whereRaw('1 = 0');
        }
    }

    protected function relations(): array
    {
        return ['branch', 'phones', 'user', 'employee'];
    }

    protected function searchable(): array
    {
        return ['name', 'email', 'tax_number'];
    }
}
