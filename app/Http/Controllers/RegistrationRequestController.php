<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegistrationRequestController extends Controller
{
    public function index(): View
    {
        return view('pharmacy.registration-requests', [
            'requests' => User::query()
                ->where('registration_status', 'pending')
                ->latest()
                ->paginate(20),
            'branches' => Branch::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function approve(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->registration_status === 'pending', 404);

        $validated = $request->validate([
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where('is_active', true),
            ],
            'role' => ['required', Rule::in(['pharmacist', 'cashier', 'accountant', 'storekeeper'])],
        ]);

        $user->forceFill([
            ...$validated,
            'is_active' => true,
            'registration_status' => 'approved',
        ])->save();

        return to_route('pharmacy.registration-requests.index')
            ->with('status', 'تم اعتماد الحساب وتحديد الفرع والصلاحية.');
    }
}
