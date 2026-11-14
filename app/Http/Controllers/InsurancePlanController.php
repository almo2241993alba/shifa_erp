<?php

namespace App\Http\Controllers;

use App\Models\InsurancePlan;
use Illuminate\Validation\Rule;

class InsurancePlanController extends PharmacyResourceController
{
    protected function modelClass(): string
    {
        return InsurancePlan::class;
    }

    protected function validationRules(?string $id = null): array
    {
        return [
            'insurer_contact_id' => [
                'required',
                'integer',
                Rule::exists('contacts', 'id')->where(fn ($query) => $query->where('is_insurance_provider', true)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255'],
            'coverage_percentage' => ['required', 'numeric', 'between:0,100'],
            'patient_copay_percentage' => ['required', 'numeric', 'between:0,100'],
            'max_coverage_per_claim' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function relations(): array
    {
        return ['insurer', 'medicineCoverages.medicine'];
    }

    protected function searchable(): array
    {
        return ['name', 'code'];
    }
}
