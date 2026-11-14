<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    use HasUuids;

    protected $fillable = [
        'financial_transaction_id',
        'branch_id',
        'supplier_id',
        'user_id',
        'treasury_id',
        'purchase_number',
        'supplier_invoice_number',
        'purchase_date',
        'received_date',
        'subtotal',
        'tax_amount',
        'additional_costs_total',
        'discount_amount',
        'grand_total',
        'paid_amount',
        'remaining_amount',
        'payment_status',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'received_date' => 'date',
            'subtotal' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'additional_costs_total' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'grand_total' => 'decimal:4',
            'paid_amount' => 'decimal:4',
            'remaining_amount' => 'decimal:4',
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
        return $this->hasMany(PurchaseItem::class);
    }

    public function medicines(): BelongsToMany
    {
        return $this->belongsToMany(Medicine::class, 'purchase_items')
            ->withPivot([
                'unit_id',
                'batch_id',
                'batch_number',
                'expiry_date',
                'quantity',
                'bonus_quantity',
                'total_base_quantity',
                'unit_purchase_price',
                'tax_amount',
                'line_total',
            ])
            ->withTimestamps();
    }

    public function additionalCosts(): HasMany
    {
        return $this->hasMany(PurchaseAdditionalCost::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }
}
