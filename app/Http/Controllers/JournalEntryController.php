<?php

namespace App\Http\Controllers;

use App\Models\JournalEntry;
use App\Services\Pharmacy\JournalEntryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JournalEntryController extends Controller
{
    public function __construct(private JournalEntryService $entries) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $query = JournalEntry::query()->with(['user', 'lines.account']);
        $this->scopeBranch($query, $request);
        $entries = $query
            ->when($validated['search'] ?? null, fn ($query, $search) => $query->where('entry_number', 'like', '%'.$search.'%'))
            ->when($validated['from'] ?? null, fn ($query, $from) => $query->whereDate('entry_date', '>=', $from))
            ->when($validated['to'] ?? null, fn ($query, $to) => $query->whereDate('entry_date', '<=', $to))
            ->latest('entry_date')
            ->paginate($validated['per_page'] ?? 20)
            ->withQueryString();

        return response()->json([
            'data' => $entries->items(),
            'meta' => [
                'current_page' => $entries->currentPage(),
                'last_page' => $entries->lastPage(),
                'per_page' => $entries->perPage(),
                'total' => $entries->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'entry_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.debit' => ['sometimes', 'numeric', 'min:0'],
            'lines.*.credit' => ['sometimes', 'numeric', 'min:0'],
            'lines.*.memo' => ['nullable', 'string', 'max:255'],
        ]);

        return response()->json(['data' => $this->entries->createManual($request->user(), $data)], 201);
    }

    public function show(string $id): JsonResponse
    {
        $query = JournalEntry::query()->with(['user', 'branch', 'lines.account']);
        $this->scopeBranch($query, request());
        $entry = $query->findOrFail($id);

        return response()->json(['data' => $entry]);
    }

    private function scopeBranch(Builder $query, Request $request): void
    {
        if ($request->user()->branch_id !== null) {
            $query->where('branch_id', $request->user()->branch_id);
        } elseif ($request->user()->role !== 'admin') {
            $query->whereRaw('1 = 0');
        }
    }
}
