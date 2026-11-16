<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleReturn extends Model
{
    use HasUuids;

    protected $fillable = [
        'financial_transaction_id',
        'branch_id',
        'sale_id',
        'pos_session_id',
        'user_id',
        'treasury_id',
        'return_number',
        'total_refund_amount',
        'patient_refund_amount',
        'insurance_refund_amount',
        'tax_refund_amount',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'total_refund_amount' => 'decimal:4',
            'patient_refund_amount' => 'decimal:4',
            'insurance_refund_amount' => 'decimal:4',
            'tax_refund_amount' => 'decimal:4',
        ];
    }

    public function financialTransaction(): BelongsTo
    {
        return $this->belongsTo(FinancialTransaction::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function posSession(): BelongsTo
    {
        return $this->belongsTo(PosSession::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function treasury(): BelongsTo
    {
        return $this->belongsTo(Treasury::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SaleReturnPayment::class);
    }

    public function medicines(): BelongsToMany
    {
        return $this->belongsToMany(Medicine::class, 'sale_return_items')
            ->withPivot([
                'sale_item_id',
                'unit_id',
                'batch_id',
                'returned_quantity',
                'base_quantity_restored',
                'unit_price_at_sale',
                'tax_refund_amount',
                'line_total',
            ])
            ->withTimestamps();
    }
}
