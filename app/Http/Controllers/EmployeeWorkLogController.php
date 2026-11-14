<?php

namespace App\Http\Controllers;

use App\Models\EmployeeWorkLog;
use App\Services\Pharmacy\EmployeeWorkLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeWorkLogController extends Controller
{
    public function __construct(private EmployeeWorkLogService $workLogs) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['nullable', 'uuid', 'exists:employees,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $query = EmployeeWorkLog::query()->with(['employee.contact', 'branch']);
        if ($request->user()->branch_id !== null) {
            $query->where('branch_id', $request->user()->branch_id);
        } elseif ($request->user()->role !== 'admin') {
            $query->whereRaw('1 = 0');
        }
        $logs = $query
            ->when($validated['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('employee_id', $employeeId))
            ->when($validated['from'] ?? null, fn ($query, $from) => $query->whereDate('work_date', '>=', $from))
            ->when($validated['to'] ?? null, fn ($query, $to) => $query->whereDate('work_date', '<=', $to))
            ->latest('work_date')
            ->paginate($validated['per_page'] ?? 20)
            ->withQueryString();

        return response()->json([
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());

        return response()->json(['data' => $this->workLogs->create($request->user(), $data)], 201);
    }

    public function show(Request $request, EmployeeWorkLog $employeeWorkLog): JsonResponse
    {
        abort_if(
            ($request->user()->branch_id === null && $request->user()->role !== 'admin')
                || ($request->user()->branch_id !== null
                    && (int) $employeeWorkLog->branch_id !== (int) $request->user()->branch_id),
            404,
        );

        return response()->json(['data' => $employeeWorkLog->load(['employee.contact', 'branch'])]);
    }

    public function update(Request $request, EmployeeWorkLog $employeeWorkLog): JsonResponse
    {
        return response()->json([
            'data' => $this->workLogs->update($employeeWorkLog, $request->user(), $request->validate($this->rules(false))),
        ]);
    }

    public function destroy(Request $request, EmployeeWorkLog $employeeWorkLog): JsonResponse
    {
        $this->workLogs->delete($employeeWorkLog, $request->user());

        return response()->json(status: 204);
    }

    private function rules(bool $creating = true): array
    {
        return [
            'employee_id' => [$creating ? 'required' : 'sometimes', 'uuid', 'exists:employees,id'],
            'work_date' => [$creating ? 'required' : 'sometimes', 'date'],
            'worked_shifts' => ['sometimes', 'numeric', 'min:0', 'max:99.9'],
            'hours_worked' => ['sometimes', 'numeric', 'min:0', 'max:24'],
            'overtime_hours' => ['sometimes', 'numeric', 'min:0', 'max:24'],
            'drawer_discrepancy' => ['sometimes', 'numeric'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
