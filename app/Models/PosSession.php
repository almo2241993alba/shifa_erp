<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSession extends Model
{
    use HasUuids;

    protected $fillable = [
        'branch_id',
        'user_id',
        'treasury_id',
        'closing_treasury_id',
        'opened_at',
        'closed_at',
        'opening_cash',
        'expected_cash',
        'actual_closing_cash',
        'cash_transfer_amount',
        'reserve_amount_at_close',
        'closing_journal_entry_id',
        'difference',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_cash' => 'decimal:4',
            'expected_cash' => 'decimal:4',
            'actual_closing_cash' => 'decimal:4',
            'cash_transfer_amount' => 'decimal:4',
            'reserve_amount_at_close' => 'decimal:4',
            'difference' => 'decimal:4',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function treasury(): BelongsTo
    {
        return $this->belongsTo(Treasury::class);
    }

    public function closingTreasury(): BelongsTo
    {
        return $this->belongsTo(Treasury::class, 'closing_treasury_id');
    }

    public function closingJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'closing_journal_entry_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function saleReturns(): HasMany
    {
        return $this->hasMany(SaleReturn::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}
