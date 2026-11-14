<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = [
        'parent_id',
        'code',
        'name',
        'account_type',
        'is_transactional',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_transactional' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function customerContacts(): HasMany
    {
        return $this->hasMany(Contact::class, 'customer_account_id');
    }

    public function supplierContacts(): HasMany
    {
        return $this->hasMany(Contact::class, 'supplier_account_id');
    }

    public function treasuries(): HasMany
    {
        return $this->hasMany(Treasury::class);
    }

    public function salesSettings(): HasMany
    {
        return $this->hasMany(Setting::class, 'sales_account_id');
    }

    public function inventorySettings(): HasMany
    {
        return $this->hasMany(Setting::class, 'inventory_account_id');
    }

    public function cogsSettings(): HasMany
    {
        return $this->hasMany(Setting::class, 'cogs_account_id');
    }

    public function vatPayableSettings(): HasMany
    {
        return $this->hasMany(Setting::class, 'vat_payable_account_id');
    }

    public function vatReceivableSettings(): HasMany
    {
        return $this->hasMany(Setting::class, 'vat_receivable_account_id');
    }

    public function expenseTypes(): HasMany
    {
        return $this->hasMany(ExpenseType::class);
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }
}
