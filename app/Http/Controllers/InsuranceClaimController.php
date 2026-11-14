<?php

namespace App\Http\Controllers;

use App\Models\InsuranceClaim;
use App\Services\Pharmacy\InsuranceClaimService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InsuranceClaimController extends Controller
{
    public function __construct(private InsuranceClaimService $claims) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:pending,approved,partially_approved,rejected,paid'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $query = InsuranceClaim::query()->with(['sale', 'policy.plan', 'insurer', 'patient']);
        $this->scopeBranch($query, $request);
        $claims = $query
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate($validated['per_page'] ?? 20)
            ->withQueryString();

        return response()->json([
            'data' => $claims->items(),
            'meta' => [
                'current_page' => $claims->currentPage(),
                'last_page' => $claims->lastPage(),
                'per_page' => $claims->perPage(),
                'total' => $claims->total(),
            ],
        ]);
    }

    public function show(InsuranceClaim $claim): JsonResponse
    {
        $this->authorizeClaim($claim);

        return response()->json([
            'data' => $claim->load(['sale.items', 'policy.plan', 'insurer', 'patient', 'submittedBy', 'processedBy']),
        ]);
    }

    public function decide(Request $request, InsuranceClaim $claim): JsonResponse
    {
        $this->authorizeClaim($claim);
        $data = $request->validate([
            'approved_amount' => ['required', 'numeric', 'min:0'],
            'rejection_reason' => ['nullable', 'string'],
            'insurer_reference' => ['nullable', 'string', 'max:255'],
        ]);

        return response()->json([
            'data' => $this->claims->decide(
                $claim,
                $request->user(),
                (float) $data['approved_amount'],
                $data['rejection_reason'] ?? null,
                $data['insurer_reference'] ?? null,
            ),
        ]);
    }

    public function recordPayment(Request $request, InsuranceClaim $claim): JsonResponse
    {
        $this->authorizeClaim($claim);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'treasury_id' => ['required', 'integer', 'exists:treasuries,id'],
            'insurer_reference' => ['nullable', 'string', 'max:255'],
        ]);

        return response()->json([
            'data' => $this->claims->recordPayment(
                $claim,
                $request->user(),
                (float) $data['amount'],
                (int) $data['treasury_id'],
                $data['insurer_reference'] ?? null,
            ),
        ]);
    }

    private function scopeBranch(Builder $query, Request $request): void
    {
        if ($request->user()->branch_id !== null) {
            $query->whereHas('sale', fn (Builder $sale) => $sale->where('branch_id', $request->user()->branch_id));
        } elseif ($request->user()->role !== 'admin') {
            $query->whereRaw('1 = 0');
        }
    }

    private function authorizeClaim(InsuranceClaim $claim): void
    {
        $user = request()->user();
        abort_if(
            ($user->branch_id === null && $user->role !== 'admin')
                || ($user->branch_id !== null && (int) $claim->sale()->value('branch_id') !== (int) $user->branch_id),
            404,
        );
    }
}
