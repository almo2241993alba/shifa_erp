<?php

namespace App\Http\Controllers;

use App\Models\PosSession;
use App\Services\Pharmacy\PosSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosSessionController extends Controller
{
    public function __construct(private PosSessionService $sessions) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:open,closed'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $sessions = PosSession::query()
            ->with(['user', 'branch', 'treasury'])
            ->when($request->user()->role !== 'admin', fn ($query) => $query->where('user_id', $request->user()->id))
            ->when($request->user()->branch_id !== null, fn ($query) => $query->where('branch_id', $request->user()->branch_id))
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest('opened_at')
            ->paginate($validated['per_page'] ?? 20)
            ->withQueryString();

        return response()->json([
            'data' => $sessions->items(),
            'meta' => [
                'current_page' => $sessions->currentPage(),
                'last_page' => $sessions->lastPage(),
                'per_page' => $sessions->perPage(),
                'total' => $sessions->total(),
            ],
        ]);
    }

    public function open(Request $request): JsonResponse
    {
        $data = $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'treasury_id' => ['required', 'integer', 'exists:treasuries,id'],
            'opening_cash' => ['sometimes', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $session = $this->sessions->open(
            $request->user(),
            (int) $data['treasury_id'],
            (float) ($data['opening_cash'] ?? 0),
            isset($data['branch_id']) ? (int) $data['branch_id'] : null,
            $data['notes'] ?? null,
        );

        return response()->json(['data' => $session->load(['branch', 'treasury'])], 201);
    }

    public function close(Request $request, PosSession $session): JsonResponse
    {
        $data = $request->validate([
            'actual_closing_cash' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        return response()->json([
            'data' => $this->sessions->close(
                $session,
                $request->user(),
                (float) $data['actual_closing_cash'],
                $data['notes'] ?? null,
            ),
        ]);
    }
}
