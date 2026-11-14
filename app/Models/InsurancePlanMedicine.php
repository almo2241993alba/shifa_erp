<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsurancePlanMedicine extends Model
{
    protected $fillable = [
        'insurance_plan_id',
        'medicine_id',
        'is_covered',
        'coverage_percentage',
        'patient_copay_percentage',
        'max_coverage_amount',
    ];

    protected function casts(): array
    {
        return [
            'is_covered' => 'boolean',
            'coverage_percentage' => 'decimal:2',
            'patient_copay_percentage' => 'decimal:2',
            'max_coverage_amount' => 'decimal:4',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(InsurancePlan::class, 'insurance_plan_id');
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }
}
