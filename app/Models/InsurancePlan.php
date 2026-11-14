<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsurancePlan extends Model
{
    protected $fillable = [
        'insurer_contact_id',
        'name',
        'code',
        'coverage_percentage',
        'patient_copay_percentage',
        'max_coverage_per_claim',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'coverage_percentage' => 'decimal:2',
            'patient_copay_percentage' => 'decimal:2',
            'max_coverage_per_claim' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function insurer(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'insurer_contact_id');
    }

    public function policies(): HasMany
    {
        return $this->hasMany(InsurancePolicy::class);
    }

    public function medicineCoverages(): HasMany
    {
        return $this->hasMany(InsurancePlanMedicine::class);
    }
}
