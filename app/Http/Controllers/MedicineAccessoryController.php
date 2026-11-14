<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\MedicineAccessory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MedicineAccessoryController extends Controller
{
    public function index(Medicine $medicine): JsonResponse
    {
        return response()->json([
            'data' => $medicine->medicineAccessories()->with('accessory')->get(),
        ]);
    }

    public function store(Request $request, Medicine $medicine): JsonResponse
    {
        $data = $request->validate($this->rules($medicine));
        $this->assertNoCycle($medicine->id, $data['accessory_id']);
        $link = $medicine->medicineAccessories()->create($data);

        return response()->json(['data' => $link->load('accessory')], 201);
    }

    public function update(Request $request, Medicine $medicine, MedicineAccessory $medicineAccessory): JsonResponse
    {
        abort_unless($medicineAccessory->medicine_id === $medicine->id, 404);
        $data = $request->validate($this->rules($medicine, $medicineAccessory));
        $accessoryId = $data['accessory_id'] ?? $medicineAccessory->accessory_id;
        $this->assertNoCycle($medicine->id, $accessoryId, $medicineAccessory->id);
        $medicineAccessory->fill($data)->save();

        return response()->json(['data' => $medicineAccessory->refresh()->load('accessory')]);
    }

    public function destroy(Medicine $medicine, MedicineAccessory $medicineAccessory): JsonResponse
    {
        abort_unless($medicineAccessory->medicine_id === $medicine->id, 404);
        $medicineAccessory->delete();

        return response()->json(status: 204);
    }

    private function rules(Medicine $medicine, ?MedicineAccessory $link = null): array
    {
        $accessoryId = [
            'required',
            'uuid',
            Rule::exists('medicines', 'id')->where('is_active', true),
        ];
        if ($link === null) {
            $accessoryId[] = Rule::unique('medicine_accessories', 'accessory_id')->where('medicine_id', $medicine->id);
        } else {
            $accessoryId[] = Rule::unique('medicine_accessories', 'accessory_id')
                ->where('medicine_id', $medicine->id)
                ->ignore($link->id);
        }

        return [
            'accessory_id' => $link === null ? $accessoryId : ['sometimes', ...$accessoryId],
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'is_free_with_parent' => ['sometimes', 'boolean'],
            'is_required' => ['sometimes', 'boolean'],
        ];
    }

    private function assertNoCycle(string $medicineId, string $accessoryId, ?int $exceptLinkId = null): void
    {
        if ($medicineId === $accessoryId) {
            abort(422, 'A medicine cannot be its own accessory.');
        }

        $frontier = [$accessoryId];
        $visited = [];
        while ($frontier !== []) {
            $current = array_shift($frontier);
            if ($current === $medicineId) {
                abort(422, 'The accessory relationship would create a cycle.');
            }
            if (isset($visited[$current])) {
                continue;
            }
            $visited[$current] = true;
            $next = MedicineAccessory::query()
                ->where('medicine_id', $current)
                ->when($exceptLinkId !== null, fn ($query) => $query->where('id', '!=', $exceptLinkId))
                ->pluck('accessory_id')
                ->all();
            $frontier = array_merge($frontier, $next);
        }
    }
}
