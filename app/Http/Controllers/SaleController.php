<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Services\Pharmacy\SaleService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function __construct(private SaleService $sales) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $query = Sale::query()->with(['customer', 'user', 'insuranceClaim']);
        $this->scopeBranch($query, $request);

        if (! empty($validated['search'])) {
            $query->where('invoice_number', 'like', '%'.$validated['search'].'%');
        }

        $sales = $query->latest()->paginate($validated['per_page'] ?? 20)->withQueryString();

        return response()->json([
            'data' => $sales->items(),
            'meta' => [
                'current_page' => $sales->currentPage(),
                'last_page' => $sales->lastPage(),
                'per_page' => $sales->perPage(),
                'total' => $sales->total(),
            ],
        ]);
    }

    public function quote(Request $request): JsonResponse
    {
        $data = $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'customer_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'insurance_policy_id' => ['nullable', 'integer', 'exists:insurance_policies,id'],
            'invoice_discount' => ['sometimes', 'numeric', 'min:0'],
            'discount_reason' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.medicine_id' => ['required', 'uuid', 'exists:medicines,id'],
            'items.*.unit_id' => ['required', 'integer', 'exists:units,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.discount_amount' => ['sometimes', 'numeric', 'min:0'],
            'items.*.discount_reason' => ['nullable', 'string', 'max:255'],
            'items.*.free_source' => ['sometimes', 'in:none,pharmacy,insurance'],
        ]);

        return response()->json(['data' => $this->sales->quote($request->user(), $data)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pos_session_id' => ['required', 'uuid', 'exists:pos_sessions,id'],
            'customer_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'insurance_policy_id' => ['nullable', 'integer', 'exists:insurance_policies,id'],
            'invoice_discount' => ['sometimes', 'numeric', 'min:0'],
            'discount_reason' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.medicine_id' => ['required', 'uuid', 'exists:medicines,id'],
            'items.*.unit_id' => ['required', 'integer', 'exists:units,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.discount_amount' => ['sometimes', 'numeric', 'min:0'],
            'items.*.discount_reason' => ['nullable', 'string', 'max:255'],
            'items.*.free_source' => ['sometimes', 'in:none,pharmacy,insurance'],
            'payments' => ['sometimes', 'array'],
            'payments.*.payment_method' => ['required', 'in:cash,card,credit'],
            'payments.*.amount' => ['required', 'numeric', 'gt:0'],
            'payments.*.reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $sale = $this->sales->create($request->user(), $data);

        return response()->json(['data' => $sale], 201);
    }

    public function show(string $id): JsonResponse
    {
        $query = Sale::query()->with([
            'items.medicine',
            'items.unit',
            'items.batch',
            'payments',
            'customer',
            'insurancePolicy.plan.insurer',
            'insuranceClaim',
            'posSession',
        ]);
        $this->scopeBranch($query, request());
        $sale = $query->findOrFail($id);

        return response()->json(['data' => $sale]);
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
