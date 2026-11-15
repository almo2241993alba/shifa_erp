<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Contact;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeePageController extends Controller
{
    public function index(): View
    {
        return view('pharmacy.employees', [
            'employees' => Employee::query()
                ->with(['contact:id,name,email', 'branch:id,name'])
                ->latest()
                ->paginate(20),
            'branches' => Branch::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'employee_number' => ['required', 'string', 'max:255', 'unique:employees,employee_number'],
            'national_id' => ['required', 'string', 'max:255', 'unique:employees,national_id'],
            'job_title' => ['required', Rule::in([
                'manager',
                'pharmacist',
                'assistant_pharmacist',
                'cashier',
                'accountant',
                'storekeeper',
                'cleaner',
            ])],
            'license_number' => ['nullable', 'string', 'max:255'],
            'license_expiry' => ['nullable', 'date'],
            'salary_type' => ['required', Rule::in(['monthly', 'daily', 'shift'])],
            'salary_basic' => ['required', 'numeric', 'min:0'],
            'allowances_fixed' => ['nullable', 'numeric', 'min:0'],
            'commission_rate' => ['nullable', 'numeric', 'between:0,100'],
            'hire_date' => ['required', 'date'],
        ]);

        DB::transaction(function () use ($validated): void {
            $contact = Contact::query()->create([
                'branch_id' => $validated['branch_id'],
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'address' => $validated['address'] ?? null,
                'is_employee' => true,
                'is_active' => true,
            ]);

            Employee::query()->create([
                ...$validated,
                'contact_id' => $contact->id,
                'allowances_fixed' => $validated['allowances_fixed'] ?? 0,
                'commission_rate' => $validated['commission_rate'] ?? 0,
                'is_active' => true,
            ]);
        });

        return to_route('pharmacy.employees.page')
            ->with('status', 'تم حفظ بيانات الموظف بنجاح.');
    }
}
