<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id',
        'medicine_id',
        'unit_id',
        'batch_id',
        'quantity',
        'base_quantity_deducted',
        'unit_price',
        'discount_amount',
        'discount_reason',
        'discounted_by_user_id',
        'discount_approved_by_user_id',
        'landed_cost_at_sale',
        'tax_amount',
        'line_total',
        'is_accessory_free',
        'free_source',
        'insurance_covered_amount',
        'patient_amount',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'base_quantity_deducted' => 'integer',
            'unit_price' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'landed_cost_at_sale' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'line_total' => 'decimal:4',
            'is_accessory_free' => 'boolean',
            'insurance_covered_amount' => 'decimal:4',
            'patient_amount' => 'decimal:4',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
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

    public function discountedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'discounted_by_user_id');
    }

    public function discountApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'discount_approved_by_user_id');
    }

    public function returnItems(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }
}
