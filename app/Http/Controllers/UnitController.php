<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Validation\Rule;

class UnitController extends PharmacyResourceController
{
    protected function modelClass(): string
    {
        return Unit::class;
    }

    protected function validationRules(?string $id = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('units', 'name')->ignore($id)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function relations(): array
    {
        return ['medicineUnits.medicine'];
    }

    protected function searchable(): array
    {
        return ['name'];
    }
}
