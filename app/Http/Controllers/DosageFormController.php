<?php

namespace App\Http\Controllers;

use App\Models\DosageForm;
use Illuminate\Validation\Rule;

class DosageFormController extends PharmacyResourceController
{
    protected function modelClass(): string
    {
        return DosageForm::class;
    }

    protected function validationRules(?string $id = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('dosage_forms', 'name')->ignore($id)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function relations(): array
    {
        return ['medicines'];
    }

    protected function searchable(): array
    {
        return ['name'];
    }
}
