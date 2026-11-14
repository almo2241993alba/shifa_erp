<?php

namespace App\Services\Pharmacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Closure;

class ResourceService
{
    public function paginate(
        string $modelClass,
        array $relations = [],
        array $searchable = [],
        ?string $search = null,
        int $perPage = 20,
        ?Closure $scope = null,
    ): LengthAwarePaginator {
        /** @var Builder $query */
        $query = $modelClass::query()->with($relations);
        if ($scope !== null) {
            $scope($query);
        }

        if ($search !== null && $search !== '' && $searchable !== []) {
            $query->where(function (Builder $query) use ($search, $searchable): void {
                foreach ($searchable as $column) {
                    $query->orWhere($column, 'like', '%'.$search.'%');
                }
            });
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    public function find(string $modelClass, string|int $id, array $relations = []): Model
    {
        return $modelClass::query()->with($relations)->findOrFail($id);
    }

    public function create(string $modelClass, array $attributes): Model
    {
        return DB::transaction(fn (): Model => $modelClass::query()->create($attributes));
    }

    public function update(Model $model, array $attributes): Model
    {
        return DB::transaction(function () use ($model, $attributes): Model {
            $locked = $model->newQuery()->lockForUpdate()->findOrFail($model->getKey());
            $locked->fill($attributes);
            $locked->save();

            return $locked->refresh();
        });
    }

    public function delete(Model $model): void
    {
        try {
            DB::transaction(fn () => $model->newQuery()->lockForUpdate()->findOrFail($model->getKey())->delete());
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '23503'], true)) {
                throw new ConflictHttpException('This record is in use and cannot be deleted.', $exception);
            }

            throw $exception;
        }
    }
}
