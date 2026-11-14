<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseReturnItem extends Model
{
    protected $fillable = [
        'purchase_return_id',
        'purchase_item_id',
        'medicine_id',
        'unit_id',
        'batch_id',
        'returned_quantity',
        'base_quantity_deducted',
        'unit_purchase_price',
        'tax_return_amount',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'returned_quantity' => 'integer',
            'base_quantity_deducted' => 'integer',
            'unit_purchase_price' => 'decimal:4',
            'tax_return_amount' => 'decimal:4',
            'line_total' => 'decimal:4',
        ];
    }

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class);
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
}
