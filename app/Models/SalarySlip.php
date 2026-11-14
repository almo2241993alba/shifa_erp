<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalarySlip extends Model
{
    use HasUuids;

    protected $fillable = [
        'branch_id',
        'employee_id',
        'financial_transaction_id',
        'treasury_id',
        'year_month_key',
        'calculated_basic_salary',
        'calculated_fixed_allowances',
        'total_commissions_earned',
        'total_overtime_pay',
        'amount_bonus',
        'total_deductions_fines',
        'previous_balance_carried_forward',
        'gross_deserved_amount',
        'actual_paid_amount',
        'balance_remaining',
        'payout_status',
        'payment_method',
        'payment_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'calculated_basic_salary' => 'decimal:4',
            'calculated_fixed_allowances' => 'decimal:4',
            'total_commissions_earned' => 'decimal:4',
            'total_overtime_pay' => 'decimal:4',
            'amount_bonus' => 'decimal:4',
            'total_deductions_fines' => 'decimal:4',
            'previous_balance_carried_forward' => 'decimal:4',
            'gross_deserved_amount' => 'decimal:4',
            'actual_paid_amount' => 'decimal:4',
            'balance_remaining' => 'decimal:4',
            'payment_date' => 'date',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function financialTransaction(): BelongsTo
    {
        return $this->belongsTo(FinancialTransaction::class);
    }

    public function treasury(): BelongsTo
    {
        return $this->belongsTo(Treasury::class);
    }
}
