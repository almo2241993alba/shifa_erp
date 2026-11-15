<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\SalarySlip;
use App\Models\Setting;
use App\Models\Treasury;
use App\Services\Pharmacy\SalarySlipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollPageController extends Controller
{
    public function __construct(private readonly SalarySlipService $salarySlips) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $employees = Employee::query()
            ->with(['contact:id,name', 'branch:id,name'])
            ->where('is_active', true);
        $slips = SalarySlip::query()->with(['employee.contact', 'branch', 'treasury']);
        $treasuries = Treasury::query()->where('is_active', true);

        if ($user->branch_id !== null) {
            $employees->where('branch_id', $user->branch_id);
            $slips->where('branch_id', $user->branch_id);
            $treasuries->where('branch_id', $user->branch_id);
        } elseif ($user->role !== 'admin') {
            $employees->whereRaw('1 = 0');
            $slips->whereRaw('1 = 0');
            $treasuries->whereRaw('1 = 0');
        }

        return view('pharmacy.payroll', [
            'employees' => $employees->orderBy('id')->get(),
            'slips' => $slips->latest('year_month_key')->paginate(20)->withQueryString(),
            'treasuries' => $treasuries->orderBy('name')->get(['id', 'branch_id', 'name', 'type', 'current_balance']),
            'currency' => Setting::query()->value('currency_code') ?: 'SDG',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'uuid', 'exists:employees,id'],
            'year_month_key' => ['required', 'date_format:Y-m'],
            'treasury_id' => ['nullable', 'integer', 'exists:treasuries,id'],
            'payment_method' => ['nullable', 'in:cash,bank_transfer'],
            'actual_paid_amount' => ['nullable', 'numeric', 'min:0'],
            'amount_bonus' => ['nullable', 'numeric', 'min:0'],
            'total_deductions_fines' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->salarySlips->generate($request->user(), $validated);

        return to_route('pharmacy.payroll.index')
            ->with('status', 'تم إنشاء مسير الراتب وتسجيل القيد المالي.');
    }
}
