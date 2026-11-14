<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsuranceClaim extends Model
{
    protected $fillable = [
        'sale_id',
        'insurance_policy_id',
        'insurer_contact_id',
        'patient_contact_id',
        'submitted_by',
        'processed_by',
        'claim_number',
        'claimed_amount',
        'approved_amount',
        'paid_amount',
        'status',
        'insurer_reference',
        'rejection_reason',
        'notes',
        'submitted_at',
        'decided_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'claimed_amount' => 'decimal:4',
            'approved_amount' => 'decimal:4',
            'paid_amount' => 'decimal:4',
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(InsurancePolicy::class, 'insurance_policy_id');
    }

    public function insurer(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'insurer_contact_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'patient_contact_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
