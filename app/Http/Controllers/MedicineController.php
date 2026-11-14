<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use Illuminate\Validation\Rule;

class MedicineController extends PharmacyResourceController
{
    protected function modelClass(): string
    {
        return Medicine::class;
    }

    protected function validationRules(?string $id = null): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'dosage_form_id' => ['required', 'integer', 'exists:dosage_forms,id'],
            'trade_name' => ['required', 'string', 'max:255'],
            'scientific_name' => ['nullable', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255', Rule::unique('medicines', 'barcode')->ignore($id)],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'item_type' => ['sometimes', Rule::in(['medicine', 'medical_supply', 'cosmetic'])],
            'dispensing_method' => ['sometimes', Rule::in(['FEFO', 'FIFO'])],
            'min_stock_alert' => ['sometimes', 'integer', 'min:0'],
            'is_taxable' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function relations(): array
    {
        return ['category', 'dosageForm', 'medicineUnits.unit', 'medicineAccessories.accessory', 'batches'];
    }

    protected function searchable(): array
    {
        return ['trade_name', 'scientific_name', 'barcode'];
    }
}
