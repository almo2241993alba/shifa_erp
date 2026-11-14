<?php

namespace App\Http\Controllers;

use App\Services\Pharmacy\ResourceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class PharmacyResourceController extends Controller
{
    public function __construct(protected ResourceService $resources) {}

    abstract protected function modelClass(): string;

    abstract protected function validationRules(?string $id = null): array;

    protected function relations(): array
    {
        return [];
    }

    protected function searchable(): array
    {
        return [];
    }

    protected function applyScope(Builder $query, Request $request): void {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $page = $this->resources->paginate(
            $this->modelClass(),
            $this->relations(),
            $this->searchable(),
            $validated['search'] ?? null,
            $validated['per_page'] ?? 20,
            fn (Builder $query) => $this->applyScope($query, $request),
        );

        return response()->json([
            'data' => $page->items(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $record = $this->resources->create(
            $this->modelClass(),
            $request->validate($this->validationRules()),
        );

        return response()->json([
            'data' => $record->load($this->relations()),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json([
            'data' => $this->findRecord($id, request()),
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $record = $this->findRecord($id, $request);
        $updated = $this->resources->update(
            $record,
            $request->validate($this->validationRules($id)),
        );

        return response()->json([
            'data' => $updated->load($this->relations()),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $record = $this->findRecord($id, request());
        $this->resources->delete($record);

        return response()->json(status: 204);
    }

    protected function findRecord(string $id, Request $request): Model
    {
        $modelClass = $this->modelClass();
        $query = $modelClass::query()->with($this->relations());
        $this->applyScope($query, $request);

        return $query->findOrFail($id);
    }
}
