<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Validation\Rule;

class BranchController extends PharmacyResourceController
{
    protected function modelClass(): string
    {
        return Branch::class;
    }

    protected function validationRules(?string $id = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', Rule::unique('branches', 'code')->ignore($id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:20'],
            'is_main' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function searchable(): array
    {
        return ['name', 'code'];
    }
}
