<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Contact extends Model
{
    protected $fillable = [
        'branch_id',
        'account_id',
        'customer_account_id',
        'supplier_account_id',
        'name',
        'email',
        'address',
        'tax_number',
        'contact_type',
        'is_customer',
        'is_supplier',
        'is_employee',
        'is_insurance_provider',
        'credit_limit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_customer' => 'boolean',
            'is_supplier' => 'boolean',
            'is_employee' => 'boolean',
            'is_insurance_provider' => 'boolean',
            'is_active' => 'boolean',
            'credit_limit' => 'decimal:4',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function customerAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'customer_account_id');
    }

    public function supplierAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'supplier_account_id');
    }

    public function phones(): HasMany
    {
        return $this->hasMany(ContactPhone::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'customer_id');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'supplier_id');
    }

    public function purchaseReturns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class, 'supplier_id');
    }

    public function insurancePlans(): HasMany
    {
        return $this->hasMany(InsurancePlan::class, 'insurer_contact_id');
    }

    public function insurancePolicies(): HasMany
    {
        return $this->hasMany(InsurancePolicy::class, 'patient_contact_id');
    }

    public function insuranceClaimsAsInsurer(): HasMany
    {
        return $this->hasMany(InsuranceClaim::class, 'insurer_contact_id');
    }

    public function insuranceClaimsAsPatient(): HasMany
    {
        return $this->hasMany(InsuranceClaim::class, 'patient_contact_id');
    }
}
