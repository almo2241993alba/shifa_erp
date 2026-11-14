<?php

namespace App\Services\Pharmacy;

use App\Models\PosSession;
use App\Models\Treasury;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosSessionService
{
    public function open(User $user, int $treasuryId, float $openingCash, ?int $branchId = null, ?string $notes = null): PosSession
    {
        return DB::transaction(function () use ($user, $treasuryId, $openingCash, $branchId, $notes): PosSession {
            $branchId ??= $user->branch_id;
            if ($branchId === null) {
                throw ValidationException::withMessages([
                    'branch_id' => 'Assign a branch to the user before opening a POS session.',
                ]);
            }
            if ($user->branch_id === null && $user->role !== 'admin') {
                abort(403, 'Assign a branch before opening a POS session.');
            }

            if ($user->branch_id !== null && (int) $branchId !== (int) $user->branch_id) {
                abort(403, 'You cannot open a session for another branch.');
            }

            if (PosSession::query()->where('user_id', $user->id)->where('status', 'open')->exists()) {
                throw ValidationException::withMessages([
                    'pos_session' => 'This user already has an open POS session.',
                ]);
            }

            $treasury = Treasury::query()
                ->whereKey($treasuryId)
                ->where('branch_id', $branchId)
                ->where('is_active', true)
                ->firstOrFail();

            return PosSession::query()->create([
                'branch_id' => $branchId,
                'user_id' => $user->id,
                'treasury_id' => $treasury->id,
                'opened_at' => now(),
                'opening_cash' => round($openingCash, 4),
                'expected_cash' => round($openingCash, 4),
                'status' => 'open',
                'notes' => $notes,
            ]);
        });
    }

    public function close(PosSession $session, User $user, float $actualCash, ?string $notes = null): PosSession
    {
        return DB::transaction(function () use ($session, $user, $actualCash, $notes): PosSession {
            $session = PosSession::query()->lockForUpdate()->findOrFail($session->id);

            if ($session->status !== 'open' || $session->user_id !== $user->id) {
                throw ValidationException::withMessages([
                    'pos_session' => 'Only the owner can close an open POS session.',
                ]);
            }

            $session->forceFill([
                'closed_at' => now(),
                'actual_closing_cash' => round($actualCash, 4),
                'difference' => round($actualCash - (float) $session->expected_cash, 4),
                'status' => 'closed',
                'notes' => $notes ?? $session->notes,
            ])->save();

            return $session->refresh();
        });
    }
}
