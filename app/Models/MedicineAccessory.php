<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicineAccessory extends Model
{
    protected $fillable = [
        'medicine_id',
        'accessory_id',
        'quantity',
        'is_free_with_parent',
        'is_required',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'is_free_with_parent' => 'boolean',
            'is_required' => 'boolean',
        ];
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function accessory(): BelongsTo
    {
        return $this->belongsTo(Medicine::class, 'accessory_id');
    }
}
