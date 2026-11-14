<?php

namespace App\Services\Pharmacy;

use App\Models\Expense;
use App\Models\ExpenseType;
use App\Models\FinancialTransaction;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PosSession;
use App\Models\Setting;
use App\Models\Treasury;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ExpenseService
{
    public function create(User $user, array $data): Expense
    {
        return DB::transaction(function () use ($user, $data): Expense {
            $branchId = (int) ($user->branch_id ?? $data['branch_id'] ?? 0);
            if ($branchId < 1) {
                throw ValidationException::withMessages([
                    'branch_id' => 'A branch is required to record an expense.',
                ]);
            }
            if ($user->branch_id === null && $user->role !== 'admin') {
                abort(403, 'Assign a branch before recording expenses.');
            }
            if ($user->branch_id !== null && isset($data['branch_id']) && (int) $data['branch_id'] !== (int) $user->branch_id) {
                abort(403, 'You cannot record expenses for another branch.');
            }

            $type = ExpenseType::query()->whereKey($data['expense_type_id'])->where('is_active', true)->firstOrFail();
            if (! $type->account->is_active || ! $type->account->is_transactional || $type->account->account_type !== 'expense') {
                throw ValidationException::withMessages([
                    'expense_type_id' => 'The expense type must use an active transactional expense account.',
                ]);
            }
            $treasury = Treasury::query()
                ->whereKey($data['treasury_id'])
                ->where('branch_id', $branchId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();
            $session = null;
            if (! empty($data['pos_session_id'])) {
                $session = PosSession::query()->lockForUpdate()->findOrFail($data['pos_session_id']);
                if ($session->status !== 'open'
                    || $session->user_id !== $user->id
                    || (int) $session->branch_id !== $branchId
                    || (int) $session->treasury_id !== (int) $treasury->id) {
                    throw ValidationException::withMessages([
                        'pos_session_id' => 'The expense must use the current user’s open session and treasury.',
                    ]);
                }
            }
            if ($user->hasRole('cashier') && $session === null) {
                throw ValidationException::withMessages([
                    'pos_session_id' => 'Cashier expenses must be recorded against the current POS session.',
                ]);
            }

            $amount = round((float) $data['amount'], 4);
            $tax = round((float) ($data['amount_tax'] ?? 0), 4);
            $total = round($amount + $tax, 4);
            if ($amount <= 0 || $tax < 0) {
                throw ValidationException::withMessages([
                    'amount' => 'The expense amount must be positive and its tax cannot be negative.',
                ]);
            }
            if ((float) $treasury->current_balance < $total) {
                throw ValidationException::withMessages([
                    'treasury_id' => 'The selected treasury does not have enough balance.',
                ]);
            }

            $transaction = FinancialTransaction::query()->create();
            $expense = Expense::query()->create([
                'financial_transaction_id' => $transaction->id,
                'branch_id' => $branchId,
                'expense_type_id' => $type->id,
                'treasury_id' => $treasury->id,
                'pos_session_id' => $session?->id,
                'user_id' => $user->id,
                'amount' => $amount,
                'amount_tax' => $tax,
                'expense_date' => $data['expense_date'],
                'notes' => $data['notes'],
            ]);

            $lines = [[
                'account_id' => $type->account_id,
                'debit' => $amount,
                'credit' => 0,
                'memo' => $type->type_name,
            ]];
            if ($tax > 0) {
                $vatAccountId = Setting::query()->value('vat_receivable_account_id');
                if ($vatAccountId === null) {
                    throw ValidationException::withMessages([
                        'settings' => 'Configure the VAT receivable account before recording taxable expenses.',
                    ]);
                }
                $lines[] = [
                    'account_id' => $vatAccountId,
                    'debit' => $tax,
                    'credit' => 0,
                    'memo' => 'Input VAT',
                ];
            }
            $lines[] = [
                'account_id' => $treasury->account_id,
                'debit' => 0,
                'credit' => $total,
                'memo' => 'Expense payment',
            ];

            $entryTransaction = FinancialTransaction::query()->create();
            $entry = JournalEntry::query()->create([
                'financial_transaction_id' => $entryTransaction->id,
                'branch_id' => $branchId,
                'user_id' => $user->id,
                'entry_number' => 'JE-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
                'entry_date' => $expense->expense_date,
                'description' => 'Automatic posting for expense '.$expense->id,
                'total_debit' => $total,
                'total_credit' => $total,
                'is_auto_generated' => true,
            ]);
            foreach ($lines as $line) {
                JournalEntryLine::query()->create([
                    'journal_entry_id' => $entry->id,
                    ...$line,
                ]);
            }

            $treasury->decrement('current_balance', $total);
            if ($session !== null) {
                $session->decrement('expected_cash', $total);
            }

            return $expense->load(['expenseType', 'treasury', 'posSession', 'user']);
        });
    }
}
