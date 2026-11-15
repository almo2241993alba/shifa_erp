<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Contact;
use App\Models\InsurancePolicy;
use App\Models\Medicine;
use App\Models\PosSession;
use App\Models\Setting;
use App\Models\Treasury;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashierPageController extends Controller
{
    public function products(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $user = $request->user();
        $branchId = $user->branch_id ?? ($user->role === 'admin' ? ($validated['branch_id'] ?? null) : null);

        if ($user->branch_id !== null && isset($validated['branch_id'])
            && (int) $validated['branch_id'] !== (int) $user->branch_id) {
            abort(403, 'You cannot view stock for another branch.');
        }

        $query = Medicine::query()
            ->where('is_active', true)
            ->with([
                'category:id,name',
                'medicineUnits.unit:id,name',
                'batches' => function (HasMany $batches) use ($branchId): void {
                    $batches->select([
                        'id',
                        'medicine_id',
                        'branch_id',
                        'remaining_quantity_base',
                        'expiry_date',
                        'is_active',
                    ])
                        ->where('is_active', true)
                        ->where('remaining_quantity_base', '>', 0)
                        ->whereDate('expiry_date', '>=', today())
                        ->when($branchId !== null, fn (Builder $query) => $query->where('branch_id', $branchId));
                },
            ])
            ->when(
                $user->branch_id === null && $user->role !== 'admin',
                fn (Builder $query) => $query->whereRaw('1 = 0'),
            )
            ->when($validated['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $medicine) use ($search): void {
                    $medicine->where('trade_name', 'like', '%'.$search.'%')
                        ->orWhere('scientific_name', 'like', '%'.$search.'%')
                        ->orWhere('barcode', 'like', '%'.$search.'%')
                        ->orWhereHas('medicineUnits', fn (Builder $units) => $units->where('unit_barcode', 'like', '%'.$search.'%'));
                });
            })
            ->orderBy('trade_name')
            ->paginate($validated['per_page'] ?? 24)
            ->withQueryString();

        return response()->json([
            'data' => $query->items(),
            'meta' => [
                'current_page' => $query->currentPage(),
                'last_page' => $query->lastPage(),
                'per_page' => $query->perPage(),
                'total' => $query->total(),
            ],
        ]);
    }

    public function __invoke(Request $request)
    {
        $user = $request->user();
        $openSession = PosSession::query()
            ->with('treasury:id,name,current_balance,drawer_reserve_amount')
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        $treasuries = Treasury::query()
            ->where('is_active', true)
            ->when($user->branch_id !== null, fn (Builder $query) => $query->where('branch_id', $user->branch_id))
            ->when($user->branch_id === null && $user->role !== 'admin', fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->orderBy('name')
            ->get(['id', 'branch_id', 'name', 'type', 'drawer_reserve_amount']);

        $branches = $user->role === 'admin'
            ? Branch::query()->where('is_active', true)->orderBy('name')->get(['id', 'name'])
            : collect();

        $patients = Contact::query()
            ->where('is_active', true)
            ->where('is_customer', true)
            ->when($user->branch_id !== null, fn (Builder $query) => $query->where('branch_id', $user->branch_id))
            ->when($user->branch_id === null && $user->role !== 'admin', fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'branch_id', 'name', 'credit_limit']);

        $policies = InsurancePolicy::query()
            ->with(['plan:id,name,coverage_percentage,patient_copay_percentage', 'patient:id,name'])
            ->where('is_active', true)
            ->whereDate('valid_from', '<=', today())
            ->where(fn (Builder $query) => $query->whereNull('valid_until')->orWhereDate('valid_until', '>=', today()))
            ->whereHas('patient', function (Builder $query) use ($user): void {
                if ($user->branch_id !== null) {
                    $query->where('branch_id', $user->branch_id);
                } elseif ($user->role !== 'admin') {
                    $query->whereRaw('1 = 0');
                }
            })
            ->orderBy('policy_number')
            ->limit(200)
            ->get(['id', 'patient_contact_id', 'insurance_plan_id', 'policy_number', 'member_number']);
        $setting = Setting::query()->first(['tax_rate', 'currency_code']);

        return view('pharmacy.cashier', [
            'openSession' => $openSession,
            'treasuries' => $treasuries,
            'branches' => $branches,
            'patients' => $patients,
            'policies' => $policies,
            'taxRate' => (float) ($setting?->tax_rate ?? 0),
            'currency' => $setting?->currency_code ?: 'SDG',
            'requiresBranchSelection' => $user->branch_id === null && $user->role === 'admin',
        ]);
    }
}
