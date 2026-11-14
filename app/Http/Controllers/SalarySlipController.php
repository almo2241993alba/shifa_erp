<?php

namespace App\Http\Controllers;

use App\Models\SalarySlip;
use App\Services\Pharmacy\SalarySlipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalarySlipController extends Controller
{
    public function __construct(private SalarySlipService $salarySlips) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['nullable', 'uuid', 'exists:employees,id'],
            'year_month_key' => ['nullable', 'date_format:Y-m'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $query = SalarySlip::query()->with(['employee.contact', 'treasury']);
        if ($request->user()->branch_id !== null) {
            $query->where('branch_id', $request->user()->branch_id);
        } elseif ($request->user()->role !== 'admin') {
            $query->whereRaw('1 = 0');
        }
        $slips = $query
            ->when($validated['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('employee_id', $employeeId))
            ->when($validated['year_month_key'] ?? null, fn ($query, $month) => $query->where('year_month_key', $month))
            ->latest('year_month_key')
            ->paginate($validated['per_page'] ?? 20)
            ->withQueryString();

        return response()->json([
            'data' => $slips->items(),
            'meta' => [
                'current_page' => $slips->currentPage(),
                'last_page' => $slips->lastPage(),
                'per_page' => $slips->perPage(),
                'total' => $slips->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'uuid', 'exists:employees,id'],
            'year_month_key' => ['required', 'date_format:Y-m'],
            'treasury_id' => ['nullable', 'integer', 'exists:treasuries,id'],
            'payment_method' => ['nullable', 'in:cash,bank_transfer'],
            'actual_paid_amount' => ['sometimes', 'numeric', 'min:0'],
            'amount_bonus' => ['sometimes', 'numeric', 'min:0'],
            'total_deductions_fines' => ['sometimes', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        return response()->json([
            'data' => $this->salarySlips->generate($request->user(), $data),
        ], 201);
    }

    public function show(Request $request, SalarySlip $salarySlip): JsonResponse
    {
        abort_if(
            ($request->user()->branch_id === null && $request->user()->role !== 'admin')
                || ($request->user()->branch_id !== null
                    && (int) $salarySlip->branch_id !== (int) $request->user()->branch_id),
            404,
        );

        return response()->json([
            'data' => $salarySlip->load(['employee.contact', 'treasury', 'branch', 'financialTransaction']),
        ]);
    }
}
