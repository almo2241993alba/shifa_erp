<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Validation\Rule;

class EmployeeController extends PharmacyResourceController
{
    protected function modelClass(): string
    {
        return Employee::class;
    }

    protected function validationRules(?string $id = null): array
    {
        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'contact_id' => ['required', 'integer', 'exists:contacts,id', Rule::unique('employees', 'contact_id')->ignore($id)],
            'employee_number' => ['required', 'string', 'max:255', Rule::unique('employees', 'employee_number')->ignore($id)],
            'national_id' => ['required', 'string', 'max:255', Rule::unique('employees', 'national_id')->ignore($id)],
            'job_title' => ['required', Rule::in(['manager', 'pharmacist', 'assistant_pharmacist', 'cashier', 'accountant', 'storekeeper', 'cleaner'])],
            'license_number' => ['nullable', 'string', 'max:255'],
            'license_expiry' => ['nullable', 'date'],
            'salary_type' => ['sometimes', Rule::in(['monthly', 'daily', 'shift'])],
            'salary_basic' => ['required', 'numeric', 'min:0'],
            'allowances_fixed' => ['sometimes', 'numeric', 'min:0'],
            'commission_rate' => ['sometimes', 'numeric', 'between:0,100'],
            'hire_date' => ['required', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function relations(): array
    {
        return ['branch', 'contact', 'workLogs', 'salarySlips'];
    }

    protected function searchable(): array
    {
        return ['employee_number', 'national_id', 'job_title'];
    }
}
