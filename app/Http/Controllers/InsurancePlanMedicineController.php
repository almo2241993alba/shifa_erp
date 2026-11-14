<?php

namespace App\Http\Controllers;

use App\Models\InsurancePlan;
use App\Models\InsurancePlanMedicine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InsurancePlanMedicineController extends Controller
{
    public function index(InsurancePlan $plan): JsonResponse
    {
        return response()->json([
            'data' => $plan->medicineCoverages()->with('medicine')->get(),
        ]);
    }

    public function store(Request $request, InsurancePlan $plan): JsonResponse
    {
        $data = $request->validate([
            'medicine_id' => [
                'required',
                'uuid',
                'exists:medicines,id',
                Rule::unique('insurance_plan_medicines', 'medicine_id')->where('insurance_plan_id', $plan->id),
            ],
            'is_covered' => ['required', 'boolean'],
            'coverage_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'patient_copay_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'max_coverage_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $coverage = $plan->medicineCoverages()->create($data);

        return response()->json(['data' => $coverage->load('medicine')], 201);
    }

    public function update(Request $request, InsurancePlan $plan, string $medicineId): JsonResponse
    {
        $coverage = $plan->medicineCoverages()->where('medicine_id', $medicineId)->firstOrFail();
        $data = $request->validate([
            'is_covered' => ['sometimes', 'boolean'],
            'coverage_percentage' => ['sometimes', 'nullable', 'numeric', 'between:0,100'],
            'patient_copay_percentage' => ['sometimes', 'nullable', 'numeric', 'between:0,100'],
            'max_coverage_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ]);
        $coverage->fill($data)->save();

        return response()->json(['data' => $coverage->refresh()->load('medicine')]);
    }

    public function destroy(InsurancePlan $plan, string $medicineId): JsonResponse
    {
        $plan->medicineCoverages()->where('medicine_id', $medicineId)->firstOrFail()->delete();

        return response()->json(status: 204);
    }
}
