<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeWorkLog extends Model
{
    protected $fillable = [
        'branch_id',
        'employee_id',
        'work_date',
        'worked_shifts',
        'hours_worked',
        'overtime_hours',
        'drawer_discrepancy',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'worked_shifts' => 'decimal:1',
            'hours_worked' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'drawer_discrepancy' => 'decimal:4',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
