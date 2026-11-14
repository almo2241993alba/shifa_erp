<?php

namespace App\Services\Pharmacy;

use App\Models\Account;
use App\Models\FinancialTransaction;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class JournalEntryService
{
    public function createManual(User $user, array $data): JournalEntry
    {
        return DB::transaction(function () use ($user, $data): JournalEntry {
            $branchId = (int) ($user->branch_id ?? $data['branch_id'] ?? 0);
            if ($branchId < 1) {
                throw ValidationException::withMessages([
                    'branch_id' => 'A branch is required for a journal entry.',
                ]);
            }
            if ($user->branch_id === null && $user->role !== 'admin') {
                abort(403, 'Assign a branch before creating journal entries.');
            }
            if ($user->branch_id !== null && isset($data['branch_id']) && (int) $data['branch_id'] !== (int) $user->branch_id) {
                abort(403, 'You cannot create journal entries for another branch.');
            }

            $debit = 0.0;
            $credit = 0.0;
            foreach ($data['lines'] as $index => &$line) {
                $account = Account::query()->whereKey($line['account_id'])->where('is_active', true)->firstOrFail();
                if (! $account->is_transactional) {
                    throw ValidationException::withMessages([
                        "lines.$index.account_id" => 'Non-transactional parent accounts cannot be posted to.',
                    ]);
                }

                $lineDebit = round((float) ($line['debit'] ?? 0), 4);
                $lineCredit = round((float) ($line['credit'] ?? 0), 4);
                if (($lineDebit > 0) === ($lineCredit > 0)) {
                    throw ValidationException::withMessages([
                        "lines.$index" => 'A journal line must have either a debit or a credit, not both.',
                    ]);
                }

                $line['debit'] = $lineDebit;
                $line['credit'] = $lineCredit;
                $debit += $lineDebit;
                $credit += $lineCredit;
            }
            unset($line);
            $debit = round($debit, 4);
            $credit = round($credit, 4);
            if ($debit <= 0 || $debit !== $credit) {
                throw ValidationException::withMessages([
                    'lines' => 'The journal entry must have equal, positive debit and credit totals.',
                ]);
            }

            $transaction = FinancialTransaction::query()->create();
            $entry = JournalEntry::query()->create([
                'financial_transaction_id' => $transaction->id,
                'branch_id' => $branchId,
                'user_id' => $user->id,
                'entry_number' => 'JE-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
                'entry_date' => $data['entry_date'],
                'description' => $data['description'],
                'total_debit' => $debit,
                'total_credit' => $credit,
                'is_auto_generated' => false,
            ]);

            foreach ($data['lines'] as $line) {
                JournalEntryLine::query()->create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'memo' => $line['memo'] ?? null,
                ]);
            }

            return $entry->load(['lines.account', 'user', 'branch']);
        });
    }
}
