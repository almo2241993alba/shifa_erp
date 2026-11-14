<?php

namespace App\Services\Pharmacy;

use App\Models\Account;
use App\Models\FinancialTransaction;
use App\Models\InsuranceClaim;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Treasury;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InsuranceClaimService
{
    public function decide(InsuranceClaim $claim, User $user, float $approvedAmount, ?string $reason = null, ?string $reference = null): InsuranceClaim
    {
        return DB::transaction(function () use ($claim, $user, $approvedAmount, $reason, $reference): InsuranceClaim {
            $claim = InsuranceClaim::query()->lockForUpdate()->findOrFail($claim->id);

            if ($claim->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => 'Only pending claims can be decided.',
                ]);
            }

            if ($approvedAmount < 0 || $approvedAmount > (float) $claim->claimed_amount) {
                throw ValidationException::withMessages([
                    'approved_amount' => 'The approved amount must be between zero and the claimed amount.',
                ]);
            }

            if ($approvedAmount === 0.0 && blank($reason)) {
                throw ValidationException::withMessages([
                    'rejection_reason' => 'A rejection reason is required when no amount is approved.',
                ]);
            }

            $claim->forceFill([
                'approved_amount' => round($approvedAmount, 4),
                'status' => $approvedAmount === 0.0
                    ? 'rejected'
                    : ($approvedAmount < (float) $claim->claimed_amount ? 'partially_approved' : 'approved'),
                'processed_by' => $user->id,
                'insurer_reference' => $reference,
                'rejection_reason' => $approvedAmount === 0.0 ? $reason : null,
                'decided_at' => now(),
            ])->save();

            return $claim->refresh();
        });
    }

    public function recordPayment(
        InsuranceClaim $claim,
        User $user,
        float $amount,
        int $treasuryId,
        ?string $reference = null,
    ): InsuranceClaim
    {
        return DB::transaction(function () use ($claim, $user, $amount, $treasuryId, $reference): InsuranceClaim {
            $claim = InsuranceClaim::query()->with('sale')->lockForUpdate()->findOrFail($claim->id);
            $approvedAmount = (float) ($claim->approved_amount ?? 0);

            if (! in_array($claim->status, ['approved', 'partially_approved'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Only approved claims can receive payment.',
                ]);
            }

            if ($amount <= 0 || round($amount, 4) !== round($approvedAmount, 4)) {
                throw ValidationException::withMessages([
                    'amount' => 'Record the full approved amount in one collection.',
                ]);
            }

            $sale = $claim->sale;
            $treasury = Treasury::query()
                ->whereKey($treasuryId)
                ->where('branch_id', $sale->branch_id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();
            $insurer = $claim->insurer()->firstOrFail();
            $receivableAccount = $insurer->customer_account_id
                ?? Account::query()->where('code', '1301')->firstOrFail()->id;
            $transaction = FinancialTransaction::query()->create();
            $entry = JournalEntry::query()->create([
                'financial_transaction_id' => $transaction->id,
                'branch_id' => $sale->branch_id,
                'user_id' => $user->id,
                'entry_number' => 'JE-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
                'entry_date' => today(),
                'description' => 'Insurance claim collection '.$claim->claim_number,
                'total_debit' => round($amount, 4),
                'total_credit' => round($amount, 4),
                'is_auto_generated' => true,
            ]);
            JournalEntryLine::query()->create([
                'journal_entry_id' => $entry->id,
                'account_id' => $treasury->account_id,
                'debit' => round($amount, 4),
                'credit' => 0,
                'memo' => 'Insurance claim collection',
            ]);
            JournalEntryLine::query()->create([
                'journal_entry_id' => $entry->id,
                'account_id' => $receivableAccount,
                'debit' => 0,
                'credit' => round($amount, 4),
                'memo' => 'Insurance receivable settled',
            ]);
            $treasury->increment('current_balance', round($amount, 4));

            $claim->forceFill([
                'paid_amount' => round($amount, 4),
                'status' => 'paid',
                'processed_by' => $user->id,
                'insurer_reference' => $reference ?? $claim->insurer_reference,
                'paid_at' => now(),
            ])->save();

            return $claim->refresh();
        });
    }
}
