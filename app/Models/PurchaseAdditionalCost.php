<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseAdditionalCost extends Model
{
    protected $fillable = [
        'purchase_id',
        'treasury_id',
        'cost_type',
        'amount',
        'notes',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:4'];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function treasury(): BelongsTo
    {
        return $this->belongsTo(Treasury::class);
    }
}
