<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Services\Pharmacy\ExpenseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function __construct(private ExpenseService $expenses) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $query = Expense::query()->with(['expenseType', 'user', 'treasury']);
        $this->scopeBranch($query, $request);
        $expenses = $query
            ->when($validated['from'] ?? null, fn ($query, $from) => $query->whereDate('expense_date', '>=', $from))
            ->when($validated['to'] ?? null, fn ($query, $to) => $query->whereDate('expense_date', '<=', $to))
            ->latest('expense_date')
            ->paginate($validated['per_page'] ?? 20)
            ->withQueryString();

        return response()->json([
            'data' => $expenses->items(),
            'meta' => [
                'current_page' => $expenses->currentPage(),
                'last_page' => $expenses->lastPage(),
                'per_page' => $expenses->perPage(),
                'total' => $expenses->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'expense_type_id' => ['required', 'integer', 'exists:expense_types,id'],
            'treasury_id' => ['required', 'integer', 'exists:treasuries,id'],
            'pos_session_id' => ['nullable', 'uuid', 'exists:pos_sessions,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'amount_tax' => ['sometimes', 'numeric', 'min:0'],
            'expense_date' => ['required', 'date'],
            'notes' => ['required', 'string'],
        ]);

        return response()->json(['data' => $this->expenses->create($request->user(), $data)], 201);
    }

    public function show(string $id): JsonResponse
    {
        $query = Expense::query()->with(['expenseType', 'treasury', 'posSession', 'user']);
        $this->scopeBranch($query, request());
        $expense = $query->findOrFail($id);

        return response()->json(['data' => $expense]);
    }

    private function scopeBranch(Builder $query, Request $request): void
    {
        if ($request->user()->branch_id !== null) {
            $query->where('branch_id', $request->user()->branch_id);
        } elseif ($request->user()->role !== 'admin') {
            $query->where('user_id', $request->user()->id);
        }
    }
}
