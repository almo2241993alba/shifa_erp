<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\Pharmacy\ResourceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct(private ResourceService $resources) {}

    public function show(): JsonResponse
    {
        return response()->json([
            'data' => Setting::query()->with([
                'salesAccount',
                'inventoryAccount',
                'cogsAccount',
                'vatPayableAccount',
                'vatReceivableAccount',
            ])->firstOrFail(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $setting = Setting::query()->firstOrFail();
        $updated = $this->resources->update($setting, $request->validate([
            'pharmacy_name' => ['sometimes', 'required', 'string', 'max:255'],
            'logo' => ['nullable', 'string', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:20'],
            'tax_rate' => ['sometimes', 'numeric', 'between:0,100'],
            'discount_approval_limit' => ['sometimes', 'numeric', 'between:0,100'],
            'currency_code' => ['sometimes', 'string', 'max:10'],
            'min_expiry_alert_days' => ['sometimes', 'integer', 'min:0'],
            'sales_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'inventory_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'cogs_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'vat_payable_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'vat_receivable_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
        ]));

        return response()->json([
            'data' => $updated->load([
                'salesAccount',
                'inventoryAccount',
                'cogsAccount',
                'vatPayableAccount',
                'vatReceivableAccount',
            ]),
        ]);
    }
}
