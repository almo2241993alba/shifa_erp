<?php

namespace App\Http\Controllers;

use App\Models\FinancialTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancialTransactionController extends Controller
{
    private array $relations = [
        'purchase',
        'sale',
        'purchaseReturn',
        'saleReturn',
        'expense',
        'salarySlip',
        'journalEntry',
    ];

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $query = FinancialTransaction::query()->with($this->relations);
        $this->scopeBranch($query, $request->user());
        $transactions = $query->latest()->paginate($validated['per_page'] ?? 20)->withQueryString();

        return response()->json([
            'data' => $transactions->items(),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $query = FinancialTransaction::query()->with($this->relations);
        $this->scopeBranch($query, $request->user());

        return response()->json(['data' => $query->findOrFail($id)]);
    }

    private function scopeBranch(Builder $query, $user): void
    {
        if ($user->role === 'admin' && $user->branch_id === null) {
            return;
        }

        if ($user->branch_id === null) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $query) use ($user): void {
            foreach ($this->relations as $index => $relation) {
                $method = $index === 0 ? 'whereHas' : 'orWhereHas';
                $query->{$method}($relation, fn (Builder $related) => $related->where('branch_id', $user->branch_id));
            }
        });
    }
}
