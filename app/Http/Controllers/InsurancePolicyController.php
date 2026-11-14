<?php

namespace App\Http\Controllers;

use App\Models\InsurancePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InsurancePolicyController extends PharmacyResourceController
{
    protected function modelClass(): string
    {
        return InsurancePolicy::class;
    }

    protected function validationRules(?string $id = null): array
    {
        $user = request()->user();

        return [
            'patient_contact_id' => [
                'required',
                'integer',
                Rule::exists('contacts', 'id')->where(function ($query) use ($user): void {
                    $query->where('is_customer', true);
                    if ($user->branch_id !== null) {
                        $query->where('branch_id', $user->branch_id);
                    } elseif ($user->role !== 'admin') {
                        $query->whereRaw('1 = 0');
                    }
                }),
            ],
            'insurance_plan_id' => [
                'required',
                'integer',
                Rule::exists('insurance_plans', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'policy_number' => ['required', 'string', 'max:255', Rule::unique('insurance_policies', 'policy_number')->ignore($id)],
            'member_number' => ['nullable', 'string', 'max:255'],
            'valid_from' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function relations(): array
    {
        return ['patient', 'plan.insurer'];
    }

    protected function searchable(): array
    {
        return ['policy_number', 'member_number'];
    }

    protected function applyScope(Builder $query, Request $request): void
    {
        if ($request->user()->branch_id !== null) {
            $query->whereHas('patient', fn (Builder $patient) => $patient->where('branch_id', $request->user()->branch_id));
        } elseif ($request->user()->role !== 'admin') {
            $query->whereRaw('1 = 0');
        }
    }
}
