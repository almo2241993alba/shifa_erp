<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BatchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'medicine_id' => ['nullable', 'uuid', 'exists:medicines,id'],
            'expires_before' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $batches = Batch::query()
            ->with(['medicine', 'branch'])
            ->when(
                $request->user()->branch_id !== null,
                fn ($query) => $query->where('branch_id', $request->user()->branch_id),
            )
            ->when($request->user()->branch_id === null && $request->user()->role !== 'admin', fn ($query) => $query->whereRaw('1 = 0'))
            ->when($validated['medicine_id'] ?? null, fn ($query, $medicineId) => $query->where('medicine_id', $medicineId))
            ->when($validated['expires_before'] ?? null, fn ($query, $date) => $query->whereDate('expiry_date', '<=', $date))
            ->latest('received_date')
            ->paginate($validated['per_page'] ?? 20)
            ->withQueryString();

        return response()->json([
            'data' => $batches->items(),
            'meta' => [
                'current_page' => $batches->currentPage(),
                'last_page' => $batches->lastPage(),
                'per_page' => $batches->perPage(),
                'total' => $batches->total(),
            ],
        ]);
    }

    public function show(Request $request, Batch $batch): JsonResponse
    {
        abort_if(
            $request->user()->role !== 'admin'
                && (int) $batch->branch_id !== (int) $request->user()->branch_id,
            404,
        );

        return response()->json(['data' => $batch->load(['medicine', 'branch', 'purchaseItems.purchase'])]);
    }
}
