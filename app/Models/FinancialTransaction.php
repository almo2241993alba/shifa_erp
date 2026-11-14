<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FinancialTransaction extends Model
{
    use HasUuids;

    public function purchase(): HasOne
    {
        return $this->hasOne(Purchase::class);
    }

    public function sale(): HasOne
    {
        return $this->hasOne(Sale::class);
    }

    public function purchaseReturn(): HasOne
    {
        return $this->hasOne(PurchaseReturn::class);
    }

    public function saleReturn(): HasOne
    {
        return $this->hasOne(SaleReturn::class);
    }

    public function expense(): HasOne
    {
        return $this->hasOne(Expense::class);
    }

    public function salarySlip(): HasOne
    {
        return $this->hasOne(SalarySlip::class);
    }

    public function journalEntry(): HasOne
    {
        return $this->hasOne(JournalEntry::class);
    }
}
