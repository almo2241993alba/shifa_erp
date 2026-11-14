<?php

namespace App\Services\Pharmacy;

use App\Models\Employee;
use App\Models\EmployeeWorkLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeWorkLogService
{
    public function create(User $user, array $data): EmployeeWorkLog
    {
        $employee = $this->employeeForUser($user, (string) $data['employee_id']);

        return DB::transaction(fn (): EmployeeWorkLog => EmployeeWorkLog::query()->create([
            ...$data,
            'branch_id' => $employee->branch_id,
        ]));
    }

    public function update(EmployeeWorkLog $workLog, User $user, array $data): EmployeeWorkLog
    {
        return DB::transaction(function () use ($workLog, $user, $data): EmployeeWorkLog {
            $workLog = EmployeeWorkLog::query()->lockForUpdate()->findOrFail($workLog->id);
            $this->assertWorkLogAccess($workLog, $user);
            $employee = $this->employeeForUser($user, (string) ($data['employee_id'] ?? $workLog->employee_id));
            $workLog->fill([
                ...$data,
                'branch_id' => $employee->branch_id,
            ])->save();

            return $workLog->refresh();
        });
    }

    public function delete(EmployeeWorkLog $workLog, User $user): void
    {
        DB::transaction(function () use ($workLog, $user): void {
            $workLog = EmployeeWorkLog::query()->lockForUpdate()->findOrFail($workLog->id);
            $this->assertWorkLogAccess($workLog, $user);
            $workLog->delete();
        });
    }

    private function employeeForUser(User $user, string $employeeId): Employee
    {
        if ($user->branch_id === null && $user->role !== 'admin') {
            abort(403, 'Assign a branch before managing employee work logs.');
        }

        $employee = Employee::query()->whereKey($employeeId)->where('is_active', true)->firstOrFail();
        if ($user->branch_id !== null && (int) $employee->branch_id !== (int) $user->branch_id) {
            abort(403, 'You cannot manage work logs for another branch.');
        }

        return $employee;
    }

    private function assertWorkLogAccess(EmployeeWorkLog $workLog, User $user): void
    {
        if ($user->branch_id === null && $user->role !== 'admin') {
            abort(403, 'Assign a branch before managing employee work logs.');
        }

        if ($user->branch_id !== null && (int) $workLog->branch_id !== (int) $user->branch_id) {
            throw ValidationException::withMessages([
                'work_log' => 'The work log belongs to another branch.',
            ]);
        }
    }
}
