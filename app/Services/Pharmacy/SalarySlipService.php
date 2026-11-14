<?php

namespace App\Services\Pharmacy;

use App\Models\Account;
use App\Models\Employee;
use App\Models\FinancialTransaction;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\SalarySlip;
use App\Models\Treasury;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SalarySlipService
{
    public function generate(User $user, array $data): SalarySlip
    {
        return DB::transaction(function () use ($user, $data): SalarySlip {
            $employee = Employee::query()
                ->with(['branch', 'contact.user'])
                ->whereKey($data['employee_id'])
                ->where('is_active', true)
                ->firstOrFail();
            if ($user->branch_id === null && $user->role !== 'admin') {
                abort(403, 'Assign a branch before generating payroll.');
            }
            if ($user->branch_id !== null && (int) $employee->branch_id !== (int) $user->branch_id) {
                abort(403, 'You cannot create payroll for another branch.');
            }
            if (SalarySlip::query()
                ->where('branch_id', $employee->branch_id)
                ->where('employee_id', $employee->id)
                ->where('year_month_key', $data['year_month_key'])
                ->exists()) {
                throw ValidationException::withMessages([
                    'year_month_key' => 'A salary slip already exists for this employee and month.',
                ]);
            }

            $period = Carbon::createFromFormat('!Y-m', $data['year_month_key']);
            $logs = $employee->workLogs()
                ->whereBetween('work_date', [$period->copy()->startOfMonth(), $period->copy()->endOfMonth()])
                ->get();
            $workedShifts = (float) $logs->sum('worked_shifts');
            $basic = match ($employee->salary_type) {
                'monthly' => (float) $employee->salary_basic,
                'daily', 'shift' => round((float) $employee->salary_basic * $workedShifts, 4),
            };
            $hourlyRate = $employee->salary_type === 'monthly'
                ? (float) $employee->salary_basic / 30 / 8
                : (float) $employee->salary_basic / 8;
            $overtime = round((float) $logs->sum('overtime_hours') * $hourlyRate * 1.5, 4);
            $commission = $this->commission($employee, $period);
            $bonus = round((float) ($data['amount_bonus'] ?? 0), 4);
            $manualDeductions = round((float) ($data['total_deductions_fines'] ?? 0), 4);
            $drawerDeficits = round($logs->sum(fn ($log) => max(0, - (float) $log->drawer_discrepancy)), 4);
            $deductions = round($manualDeductions + $drawerDeficits, 4);
            $previousBalance = (float) SalarySlip::query()
                ->where('branch_id', $employee->branch_id)
                ->where('employee_id', $employee->id)
                ->where('year_month_key', '<', $data['year_month_key'])
                ->orderByDesc('year_month_key')
                ->value('balance_remaining');
            $allowances = (float) $employee->allowances_fixed;
            $currentEarnings = round($basic + $allowances + $commission + $overtime + $bonus - $deductions, 4);
            if ($currentEarnings < 0) {
                throw ValidationException::withMessages([
                    'total_deductions_fines' => 'Deductions cannot exceed current month earnings.',
                ]);
            }

            $gross = round($currentEarnings + $previousBalance, 4);
            $paid = round((float) ($data['actual_paid_amount'] ?? 0), 4);
            if ($paid < 0 || $paid > $gross) {
                throw ValidationException::withMessages([
                    'actual_paid_amount' => 'The paid amount must be between zero and the gross entitlement.',
                ]);
            }

            $treasury = null;
            $paymentMethod = $data['payment_method'] ?? null;
            if ($paid > 0) {
                if ($paymentMethod === null || empty($data['treasury_id'])) {
                    throw ValidationException::withMessages([
                        'payment_method' => 'A payment method and treasury are required for payroll payments.',
                    ]);
                }
                $treasury = Treasury::query()
                    ->whereKey($data['treasury_id'])
                    ->where('branch_id', $employee->branch_id)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->firstOrFail();
                if (($paymentMethod === 'bank_transfer' && $treasury->type !== 'bank')
                    || ($paymentMethod === 'cash' && $treasury->type === 'bank')) {
                    throw ValidationException::withMessages([
                        'treasury_id' => 'The treasury type does not match the selected payment method.',
                    ]);
                }
                if ((float) $treasury->current_balance < $paid) {
                    throw ValidationException::withMessages([
                        'treasury_id' => 'The selected treasury does not have enough balance.',
                    ]);
                }
            }

            $financialTransaction = FinancialTransaction::query()->create();
            $slip = SalarySlip::query()->create([
                'branch_id' => $employee->branch_id,
                'employee_id' => $employee->id,
                'financial_transaction_id' => $financialTransaction->id,
                'treasury_id' => $treasury?->id,
                'year_month_key' => $data['year_month_key'],
                'calculated_basic_salary' => $basic,
                'calculated_fixed_allowances' => $allowances,
                'total_commissions_earned' => $commission,
                'total_overtime_pay' => $overtime,
                'amount_bonus' => $bonus,
                'total_deductions_fines' => $deductions,
                'previous_balance_carried_forward' => $previousBalance,
                'gross_deserved_amount' => $gross,
                'actual_paid_amount' => $paid,
                'balance_remaining' => round($gross - $paid, 4),
                'payout_status' => $paid >= $gross
                    ? 'paid'
                    : ($paid > 0 ? 'partially_paid' : 'unpaid'),
                'payment_method' => $paid > 0 ? $paymentMethod : null,
                'payment_date' => $paid > 0 ? today() : null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->postJournal($slip, $currentEarnings, $previousBalance, $paid, $treasury, $user);
            if ($paid > 0 && $treasury !== null) {
                $treasury->decrement('current_balance', $paid);
            }

            return $slip->load(['employee.contact', 'treasury', 'branch']);
        });
    }

    private function commission(Employee $employee, Carbon $period): float
    {
        $user = $employee->contact?->user;
        if ($user === null || (float) $employee->commission_rate <= 0) {
            return 0;
        }

        $sales = (float) $user->sales()
            ->whereBetween('created_at', [$period->copy()->startOfMonth(), $period->copy()->endOfMonth()])
            ->sum('grand_total');

        return round($sales * (float) $employee->commission_rate / 100, 4);
    }

    private function postJournal(
        SalarySlip $slip,
        float $currentEarnings,
        float $previousBalance,
        float $paid,
        ?Treasury $treasury,
        User $user,
    ): void {
        $salaryExpense = Account::query()->where('code', '5201')->firstOrFail();
        $payrollPayable = Account::query()->where('code', '2301')->firstOrFail();
        $lines = [];
        $this->addLine($lines, $salaryExpense->id, $currentEarnings, 0, 'Current payroll expense');
        $this->addLine($lines, $payrollPayable->id, $previousBalance, 0, 'Prior payroll balance carried forward');
        if ($paid > 0 && $treasury !== null) {
            $this->addLine($lines, $treasury->account_id, 0, $paid, 'Salary payment');
        }
        $remaining = round((float) $slip->balance_remaining, 4);
        $this->addLine($lines, $payrollPayable->id, 0, $remaining, 'Payroll payable');

        $totalDebit = round(array_sum(array_column($lines, 'debit')), 4);
        $totalCredit = round(array_sum(array_column($lines, 'credit')), 4);
        if ($totalDebit !== $totalCredit) {
            throw ValidationException::withMessages([
                'accounting' => 'The salary slip could not be posted because its journal entry is not balanced.',
            ]);
        }

        $transaction = FinancialTransaction::query()->create();
        $entry = JournalEntry::query()->create([
            'financial_transaction_id' => $transaction->id,
            'branch_id' => $slip->branch_id,
            'user_id' => $user->id,
            'entry_number' => 'JE-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
            'entry_date' => $slip->payment_date ?? today(),
            'description' => 'Automatic posting for payroll '.$slip->year_month_key,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'is_auto_generated' => true,
        ]);
        foreach ($lines as $line) {
            JournalEntryLine::query()->create([
                'journal_entry_id' => $entry->id,
                ...$line,
            ]);
        }
    }

    private function addLine(array &$lines, int $accountId, float $debit, float $credit, string $memo): void
    {
        if (max($debit, $credit) > 0) {
            $lines[] = [
                'account_id' => $accountId,
                'debit' => round($debit, 4),
                'credit' => round($credit, 4),
                'memo' => $memo,
            ];
        }
    }
}
