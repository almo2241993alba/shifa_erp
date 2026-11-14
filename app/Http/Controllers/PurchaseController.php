<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Services\Pharmacy\PurchaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PurchaseController extends Controller
{
    public function __construct(private PurchaseService $purchases) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['draft', 'received', 'cancelled'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $query = Purchase::query()->with(['supplier', 'user', 'items.medicine']);
        $this->scopeBranch($query, $request);
        $purchases = $query
            ->when($validated['search'] ?? null, function ($query, $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('purchase_number', 'like', '%'.$search.'%')
                        ->orWhere('supplier_invoice_number', 'like', '%'.$search.'%');
                });
            })
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest('purchase_date')
            ->paginate($validated['per_page'] ?? 20)
            ->withQueryString();

        return response()->json([
            'data' => $purchases->items(),
            'meta' => [
                'current_page' => $purchases->currentPage(),
                'last_page' => $purchases->lastPage(),
                'per_page' => $purchases->perPage(),
                'total' => $purchases->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'supplier_id' => ['required', 'integer', 'exists:contacts,id'],
            'treasury_id' => ['nullable', 'integer', 'exists:treasuries,id'],
            'supplier_invoice_number' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['required', 'date'],
            'received_date' => ['required', 'date', 'after_or_equal:purchase_date'],
            'discount_amount' => ['sometimes', 'numeric', 'min:0'],
            'paid_amount' => ['sometimes', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.medicine_id' => ['required', 'uuid', 'exists:medicines,id'],
            'items.*.unit_id' => ['required', 'integer', 'exists:units,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.bonus_quantity' => ['sometimes', 'integer', 'min:0'],
            'items.*.unit_purchase_price' => ['required', 'numeric', 'gt:0'],
            'items.*.new_selling_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.batch_number' => ['required', 'string', 'max:255'],
            'items.*.production_date' => ['nullable', 'date', 'before_or_equal:today'],
            'items.*.expiry_date' => ['required', 'date', 'after_or_equal:today'],
            'additional_costs' => ['sometimes', 'array'],
            'additional_costs.*.treasury_id' => ['required', 'integer', 'exists:treasuries,id'],
            'additional_costs.*.cost_type' => ['required', 'string', 'max:255'],
            'additional_costs.*.amount' => ['required', 'numeric', 'gt:0'],
            'additional_costs.*.notes' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $purchase = $this->purchases->create($request->user(), $data);

        return response()->json(['data' => $purchase], 201);
    }

    public function show(string $id): JsonResponse
    {
        $query = Purchase::query()->with([
            'supplier',
            'user',
            'treasury',
            'items.medicine',
            'items.unit',
            'items.batch',
            'additionalCosts.treasury',
            'returns.items',
        ]);
        $this->scopeBranch($query, request());
        $purchase = $query->findOrFail($id);

        return response()->json(['data' => $purchase]);
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
