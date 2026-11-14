<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sale extends Model
{
    use HasUuids;

    protected $fillable = [
        'financial_transaction_id',
        'branch_id',
        'pos_session_id',
        'user_id',
        'customer_id',
        'insurance_policy_id',
        'treasury_id',
        'invoice_number',
        'session_receipt_number',
        'subtotal',
        'discount_amount',
        'discount_reason',
        'discounted_by_user_id',
        'discount_approved_by_user_id',
        'tax_amount',
        'grand_total',
        'patient_due',
        'insurance_due',
        'paid_amount',
        'change_amount',
        'payment_method',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'session_receipt_number' => 'integer',
            'subtotal' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'patient_due' => 'decimal:4',
            'insurance_due' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'grand_total' => 'decimal:4',
            'paid_amount' => 'decimal:4',
            'change_amount' => 'decimal:4',
        ];
    }

    public function financialTransaction(): BelongsTo
    {
        return $this->belongsTo(FinancialTransaction::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function posSession(): BelongsTo
    {
        return $this->belongsTo(PosSession::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'customer_id');
    }

    public function insurancePolicy(): BelongsTo
    {
        return $this->belongsTo(InsurancePolicy::class);
    }

    public function discountedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'discounted_by_user_id');
    }

    public function discountApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'discount_approved_by_user_id');
    }

    public function treasury(): BelongsTo
    {
        return $this->belongsTo(Treasury::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function medicines(): BelongsToMany
    {
        return $this->belongsToMany(Medicine::class, 'sale_items')
            ->withPivot([
                'unit_id',
                'batch_id',
                'quantity',
                'base_quantity_deducted',
                'unit_price',
                'landed_cost_at_sale',
                'discount_amount',
                'tax_amount',
                'line_total',
                'is_accessory_free',
                'free_source',
                'insurance_covered_amount',
                'patient_amount',
            ])
            ->withTimestamps();
    }

    public function returns(): HasMany
    {
        return $this->hasMany(SaleReturn::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function insuranceClaim(): HasOne
    {
        return $this->hasOne(InsuranceClaim::class);
    }
}
