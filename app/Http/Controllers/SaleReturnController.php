<?php

namespace App\Http\Controllers;

use App\Models\SaleReturn;
use App\Services\Pharmacy\SaleReturnService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleReturnController extends Controller
{
    public function __construct(private SaleReturnService $returns) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $query = SaleReturn::query()->with(['sale', 'user', 'items.medicine', 'payments.salePayment', 'payments.treasury']);
        $this->scopeBranch($query, $request);
        $returns = $query
            ->latest()
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
            'sale_id' => ['required', 'uuid', 'exists:sales,id'],
            'pos_session_id' => ['required', 'uuid', 'exists:pos_sessions,id'],
            'treasury_id' => ['required', 'integer', 'exists:treasuries,id'],
            'reason' => ['required', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sale_item_id' => ['required', 'integer', 'distinct', 'exists:sale_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        return response()->json([
            'data' => $this->returns->create($request->user(), $data),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $query = SaleReturn::query()->with([
            'sale',
            'user',
            'posSession',
            'treasury',
            'items.saleItem',
            'items.medicine',
            'items.batch',
            'payments.salePayment',
            'payments.treasury',
        ]);
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
