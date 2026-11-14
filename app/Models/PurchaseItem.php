<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id',
        'medicine_id',
        'unit_id',
        'batch_id',
        'batch_number',
        'production_date',
        'expiry_date',
        'quantity',
        'bonus_quantity',
        'total_base_quantity',
        'unit_purchase_price',
        'new_selling_price',
        'tax_amount',
        'allocated_additional_cost',
        'landed_cost_per_base_unit',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'production_date' => 'date',
            'expiry_date' => 'date',
            'quantity' => 'integer',
            'bonus_quantity' => 'integer',
            'total_base_quantity' => 'integer',
            'unit_purchase_price' => 'decimal:4',
            'new_selling_price' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'allocated_additional_cost' => 'decimal:4',
            'landed_cost_per_base_unit' => 'decimal:4',
            'line_total' => 'decimal:4',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function returnItems(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }
}
