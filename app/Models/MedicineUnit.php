<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicineUnit extends Model
{
    protected $fillable = [
        'medicine_id',
        'unit_id',
        'conversion_factor',
        'selling_price',
        'unit_barcode',
        'is_base_unit',
        'is_purchasable',
        'is_sellable',
    ];

    protected function casts(): array
    {
        return [
            'conversion_factor' => 'integer',
            'selling_price' => 'decimal:4',
            'is_base_unit' => 'boolean',
            'is_purchasable' => 'boolean',
            'is_sellable' => 'boolean',
        ];
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
