<?php

namespace App\Http\Controllers;

use App\Models\PurchaseReturn;
use App\Services\Pharmacy\PurchaseReturnService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseReturnController extends Controller
{
    public function __construct(private PurchaseReturnService $returns) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $query = PurchaseReturn::query()->with(['purchase', 'supplier', 'user', 'items.medicine']);
        $this->scopeBranch($query, $request);
        $returns = $query
            ->latest('return_date')
            ->paginate($validated['per_page'] ?? 20)
            ->withQueryString();

        return response()->json([
            'data' => $returns->items(),
            'meta' => [
                'current_page' => $returns->currentPage(),
                'last_page' => $returns->lastPage(),
                'per_page' => $returns->perPage(),
                'total' => $returns->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'purchase_id' => ['required', 'uuid', 'exists:purchases,id'],
            'return_date' => ['required', 'date'],
            'treasury_id' => ['nullable', 'integer', 'exists:treasuries,id'],
            'reason' => ['required', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_item_id' => ['required', 'integer', 'distinct', 'exists:purchase_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        return response()->json([
            'data' => $this->returns->create($request->user(), $data),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $query = PurchaseReturn::query()->with(['purchase', 'supplier', 'user', 'treasury', 'items.purchaseItem', 'items.medicine', 'items.batch']);
        $this->scopeBranch($query, request());
        $return = $query->findOrFail($id);

        return response()->json(['data' => $return]);
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
