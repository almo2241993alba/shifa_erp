<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Validation\Rule;

class CategoryController extends PharmacyResourceController
{
    protected function modelClass(): string
    {
        return Category::class;
    }

    protected function validationRules(?string $id = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($id)],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function relations(): array
    {
        return ['medicines'];
    }

    protected function searchable(): array
    {
        return ['name'];
    }
}
