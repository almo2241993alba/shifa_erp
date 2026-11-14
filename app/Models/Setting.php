<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    protected $fillable = [
        'pharmacy_name',
        'logo',
        'tax_number',
        'tax_rate',
        'discount_approval_limit',
        'currency_code',
        'min_expiry_alert_days',
        'sales_account_id',
        'inventory_account_id',
        'cogs_account_id',
        'vat_payable_account_id',
        'vat_receivable_account_id',
    ];

    protected function casts(): array
    {
        return [
            'tax_rate' => 'decimal:2',
            'discount_approval_limit' => 'decimal:2',
            'min_expiry_alert_days' => 'integer',
        ];
    }

    public function salesAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'sales_account_id');
    }

    public function inventoryAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'inventory_account_id');
    }

    public function cogsAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'cogs_account_id');
    }

    public function vatPayableAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'vat_payable_account_id');
    }

    public function vatReceivableAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'vat_receivable_account_id');
    }
}
