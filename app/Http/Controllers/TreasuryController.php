<?php

namespace App\Http\Controllers;

use App\Models\Treasury;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TreasuryController extends PharmacyResourceController
{
    protected function modelClass(): string
    {
        return Treasury::class;
    }

    protected function validationRules(?string $id = null): array
    {
        return [
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where(function ($query): void {
                    $user = request()->user();
                    if ($user->branch_id !== null) {
                        $query->where('id', $user->branch_id);
                    } elseif ($user->role !== 'admin') {
                        $query->whereRaw('1 = 0');
                    }
                }),
            ],
            'account_id' => [
                'required',
                'integer',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query
                    ->where('account_type', 'asset')
                    ->where('is_transactional', true)
                    ->where('is_active', true)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['cash_drawer', 'main_safe', 'bank'])],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function relations(): array
    {
        return ['branch', 'account'];
    }

    protected function searchable(): array
    {
        return ['name'];
    }

    protected function applyScope(Builder $query, Request $request): void
    {
        if ($request->user()->branch_id !== null) {
            $query->where('branch_id', $request->user()->branch_id);
        } elseif ($request->user()->role !== 'admin') {
            $query->whereRaw('1 = 0');
        }
    }
}
