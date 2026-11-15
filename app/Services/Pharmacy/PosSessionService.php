<?php

namespace App\Services\Pharmacy;

use App\Models\PosSession;
use App\Models\FinancialTransaction;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Treasury;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
                ->where('type', 'cash_drawer')
                ->lockForUpdate()
                ->first();
            if ($treasury === null) {
                throw ValidationException::withMessages([
                    'treasury_id' => 'Select an active cash drawer in the session branch.',
                ]);
            }
            $openingCash = round($openingCash, 4);
            $treasury->forceFill(['current_balance' => $openingCash])->save();

            return PosSession::query()->create([
                'branch_id' => $branchId,
                'user_id' => $user->id,
                'treasury_id' => $treasury->id,
                'opened_at' => now(),
                'opening_cash' => $openingCash,
                'expected_cash' => $openingCash,
                'status' => 'open',
                'notes' => $notes,
            ]);
        });
    }

    public function close(
        PosSession $session,
        User $user,
        float $actualCash,
        int $closingTreasuryId,
        ?string $notes = null,
    ): PosSession
    {
        return DB::transaction(function () use ($session, $user, $actualCash, $closingTreasuryId, $notes): PosSession {
            $session = PosSession::query()->lockForUpdate()->findOrFail($session->id);

            if ($session->status !== 'open' || $session->user_id !== $user->id) {
                throw ValidationException::withMessages([
                    'pos_session' => 'Only the owner can close an open POS session.',
                ]);
            }

            $drawer = Treasury::query()->whereKey($session->treasury_id)->lockForUpdate()->firstOrFail();
            $safe = Treasury::query()
                ->whereKey($closingTreasuryId)
                ->where('branch_id', $session->branch_id)
                ->where('type', 'main_safe')
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();
            $actualCash = round($actualCash, 4);
            $reserveAmount = round((float) $drawer->drawer_reserve_amount, 4);
            $transferAmount = round(max(0, $actualCash - $reserveAmount), 4);

            $drawer->forceFill(['current_balance' => $actualCash])->save();
            if ($transferAmount > 0) {
                $drawer->decrement('current_balance', $transferAmount);
                $safe->increment('current_balance', $transferAmount);
            }

            $journalEntry = $transferAmount > 0
                ? $this->postCashTransfer($session, $drawer, $safe, $transferAmount, $user)
                : null;

            $session->forceFill([
                'closed_at' => now(),
                'closing_treasury_id' => $safe->id,
                'actual_closing_cash' => $actualCash,
                'cash_transfer_amount' => $transferAmount,
                'reserve_amount_at_close' => $reserveAmount,
                'closing_journal_entry_id' => $journalEntry?->id,
                'difference' => round($actualCash - (float) $session->expected_cash, 4),
                'status' => 'closed',
                'notes' => $notes ?? $session->notes,
            ])->save();

            return $session->refresh()->load(['treasury', 'closingTreasury', 'closingJournalEntry']);
        });
    }

    private function postCashTransfer(
        PosSession $session,
        Treasury $drawer,
        Treasury $safe,
        float $amount,
        User $user,
    ): JournalEntry {
        $transaction = FinancialTransaction::query()->create();
        $entry = JournalEntry::query()->create([
            'financial_transaction_id' => $transaction->id,
            'branch_id' => $session->branch_id,
            'user_id' => $user->id,
            'entry_number' => 'JE-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
            'entry_date' => today(),
            'description' => 'POS cash drawer settlement for session '.$session->id,
            'total_debit' => $amount,
            'total_credit' => $amount,
            'is_auto_generated' => true,
        ]);

        foreach ([
            ['account_id' => $safe->account_id, 'debit' => $amount, 'credit' => 0, 'memo' => 'Cash deposited to main safe'],
            ['account_id' => $drawer->account_id, 'debit' => 0, 'credit' => $amount, 'memo' => 'Cash transferred from POS drawer'],
        ] as $line) {
            JournalEntryLine::query()->create([
                'journal_entry_id' => $entry->id,
                ...$line,
            ]);
        }

        return $entry;
    }
}
