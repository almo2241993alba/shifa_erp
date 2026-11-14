<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReturn extends Model
{
    use HasUuids;

    protected $fillable = [
        'financial_transaction_id',
        'branch_id',
        'purchase_id',
        'supplier_id',
        'user_id',
        'treasury_id',
        'return_number',
        'return_date',
        'total_return_amount',
        'tax_return_amount',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'return_date' => 'date',
            'total_return_amount' => 'decimal:4',
            'tax_return_amount' => 'decimal:4',
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

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'supplier_id');
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
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function medicines(): BelongsToMany
    {
        return $this->belongsToMany(Medicine::class, 'purchase_return_items')
            ->withPivot([
                'purchase_item_id',
                'unit_id',
                'batch_id',
                'returned_quantity',
                'base_quantity_deducted',
                'unit_purchase_price',
                'tax_return_amount',
                'line_total',
            ])
            ->withTimestamps();
    }
}
