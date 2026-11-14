<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasUuids;

    protected $fillable = [
        'branch_id',
        'contact_id',
        'employee_number',
        'national_id',
        'job_title',
        'license_number',
        'license_expiry',
        'salary_type',
        'salary_basic',
        'allowances_fixed',
        'commission_rate',
        'hire_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'license_expiry' => 'date',
            'salary_basic' => 'decimal:4',
            'allowances_fixed' => 'decimal:4',
            'commission_rate' => 'decimal:2',
            'hire_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function workLogs(): HasMany
    {
        return $this->hasMany(EmployeeWorkLog::class);
    }

    public function salarySlips(): HasMany
    {
        return $this->hasMany(SalarySlip::class);
    }
}
