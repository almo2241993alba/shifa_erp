<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\MedicineUnit;
use App\Models\PurchaseItem;
use App\Models\SaleItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MedicineUnitController extends Controller
{
    public function index(Medicine $medicine): JsonResponse
    {
        return response()->json([
            'data' => $medicine->medicineUnits()->with('unit')->get(),
        ]);
    }

    public function store(Request $request, Medicine $medicine): JsonResponse
    {
        $data = $request->validate([
            'unit_id' => [
                'required',
                'integer',
                Rule::exists('units', 'id')->where('is_active', true),
                Rule::unique('medicine_units', 'unit_id')->where('medicine_id', $medicine->id),
            ],
            'conversion_factor' => ['required', 'integer', 'min:1'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'unit_barcode' => ['nullable', 'string', 'max:255', 'unique:medicine_units,unit_barcode'],
            'is_base_unit' => ['required', 'boolean'],
            'is_purchasable' => ['required', 'boolean'],
            'is_sellable' => ['required', 'boolean'],
        ]);

        if ($data['is_base_unit'] && (int) $data['conversion_factor'] !== 1) {
            abort(422, 'The base unit conversion factor must be 1.');
        }

        $medicineUnit = DB::transaction(function () use ($medicine, $data): MedicineUnit {
            $medicine->newQuery()->lockForUpdate()->findOrFail($medicine->id);
            if (filter_var($data['is_base_unit'], FILTER_VALIDATE_BOOLEAN)) {
                $medicine->medicineUnits()->update(['is_base_unit' => false]);
            } elseif (! $medicine->medicineUnits()->exists()) {
                abort(422, 'The first unit configured for a medicine must be its base unit.');
            }

            return $medicine->medicineUnits()->create($data);
        });

        return response()->json(['data' => $medicineUnit->load('unit')], 201);
    }

    public function update(Request $request, Medicine $medicine, MedicineUnit $medicineUnit): JsonResponse
    {
        abort_unless($medicineUnit->medicine_id === $medicine->id, 404);
        $data = $request->validate([
            'conversion_factor' => ['sometimes', 'integer', 'min:1'],
            'selling_price' => ['sometimes', 'numeric', 'min:0'],
            'unit_barcode' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('medicine_units', 'unit_barcode')->ignore($medicineUnit->id)],
            'is_base_unit' => ['sometimes', 'boolean'],
            'is_purchasable' => ['sometimes', 'boolean'],
            'is_sellable' => ['sometimes', 'boolean'],
        ]);

        if (filter_var($data['is_base_unit'] ?? false, FILTER_VALIDATE_BOOLEAN)
            && (int) ($data['conversion_factor'] ?? $medicineUnit->conversion_factor) !== 1) {
            abort(422, 'The base unit conversion factor must be 1.');
        }

        $medicineUnit = DB::transaction(function () use ($medicine, $medicineUnit, $data): MedicineUnit {
            $medicine->newQuery()->lockForUpdate()->findOrFail($medicine->id);
            $medicineUnit = MedicineUnit::query()->lockForUpdate()->findOrFail($medicineUnit->id);
            if (isset($data['conversion_factor'])
                && (int) $data['conversion_factor'] !== (int) $medicineUnit->conversion_factor
                && $this->hasTransactionHistory($medicineUnit)) {
                abort(409, 'A unit conversion factor cannot change after the unit has been used in transactions.');
            }
            $isBaseUnit = array_key_exists('is_base_unit', $data)
                ? filter_var($data['is_base_unit'], FILTER_VALIDATE_BOOLEAN)
                : null;
            if ($isBaseUnit === true) {
                $medicine->medicineUnits()->where('id', '!=', $medicineUnit->id)->update(['is_base_unit' => false]);
            } elseif ($isBaseUnit === false
                && $medicineUnit->is_base_unit
                && ! $medicine->medicineUnits()->where('id', '!=', $medicineUnit->id)->where('is_base_unit', true)->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'is_base_unit' => 'A medicine must always have a base unit.',
                ]);
            }

            $medicineUnit->fill($data)->save();

            return $medicineUnit->refresh();
        });

        return response()->json(['data' => $medicineUnit->load('unit')]);
    }

    public function destroy(Medicine $medicine, MedicineUnit $medicineUnit): JsonResponse
    {
        abort_unless($medicineUnit->medicine_id === $medicine->id, 404);
        if ($this->hasTransactionHistory($medicineUnit)) {
            abort(409, 'A medicine unit used in transactions cannot be deleted.');
        }
        if ($medicineUnit->is_base_unit && $medicine->medicineUnits()->where('id', '!=', $medicineUnit->id)->exists()) {
            abort(409, 'Choose another base unit before deleting this one.');
        }
        $medicineUnit->delete();

        return response()->json(status: 204);
    }

    private function hasTransactionHistory(MedicineUnit $medicineUnit): bool
    {
        return PurchaseItem::query()
            ->where('medicine_id', $medicineUnit->medicine_id)
            ->where('unit_id', $medicineUnit->unit_id)
            ->exists()
            || SaleItem::query()
                ->where('medicine_id', $medicineUnit->medicine_id)
                ->where('unit_id', $medicineUnit->unit_id)
                ->exists();
    }
}
