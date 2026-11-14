<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleReturnItem extends Model
{
    protected $fillable = [
        'sale_return_id',
        'sale_item_id',
        'medicine_id',
        'unit_id',
        'batch_id',
        'returned_quantity',
        'base_quantity_restored',
        'unit_price_at_sale',
        'tax_refund_amount',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'returned_quantity' => 'integer',
            'base_quantity_restored' => 'integer',
            'unit_price_at_sale' => 'decimal:4',
            'tax_refund_amount' => 'decimal:4',
            'line_total' => 'decimal:4',
        ];
    }

    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
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
