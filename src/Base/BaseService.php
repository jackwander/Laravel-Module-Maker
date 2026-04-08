<?php

namespace Jackwander\ModuleMaker\Base;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BaseService
{
    protected Model $entity;

    public function __construct(Model $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Get the currently authenticated user for the api guard.
     */
    public function user()
    {
        return auth('api')->user();
    }

    /**
     * Apply filters and search to the query.
     */
    public function filter(array $input, $entity = null): Builder
    {
        $query = $entity ?? $this->entity->newQuery();

        $search  = $input['search'] ?? null;
        $orderBy = $input['orderBy'] ?? [];
        
        // Use the model's fillable columns for searching without hitting the DB
        $columns = $this->entity->getFillable();

        foreach ($orderBy as $key => $direction) {
            $query->orderBy($key, $direction);
        }

        if ($search) {
            $query->where(function (Builder $q) use ($search, $columns) {
                foreach ($columns as $column) {
                    $q->orWhere($column, 'like', $search . '%');
                }
            });
        }

        return $query;
    }

    /**
     * Paginate the filtered results.
     */
    public function paginateWithFilters(array $input): LengthAwarePaginator
    {
        $size = $input['size'] ?? 10;

        return $this->filter($input)->paginate($size)->appends($input);
    }

    public function create(array $data): Model
    {
        return $this->entity->create($data);
    }

    public function find($id): Model
    {
        return $this->entity->withoutGlobalScopes()->findOrFail($id);
    }

    public function findWithTrashed($id): ?Model
    {
        return $this->entity->where('id', $id)->withTrashed()->first();
    }

    public function findBy($columns, $value): Collection
    {
        $query = $this->entity->newQuery();

        if (is_array($columns)) {
            foreach ($columns as $key => $column) {
                $query = ($key === 0) ? $query->where($column, $value) : $query->orWhere($column, $value);
            }
            return $query->get();
        }

        return $query->where($columns, $value)->get();
    }

    public function findFirstBy(string $column, $value): ?Model
    {
        return $this->entity->where($column, $value)->first();
    }

    public function findForPassport(string $input): ?Model
    {
        return $this->entity
            ->where('email', $input)
            ->orWhere('username', $input)
            ->first();
    }

    public function update(array $data, $identifier): bool
    {
        $model = $this->find($identifier);
        
        return $model->update($data);
    }

    public function all(): Collection
    {
        return $this->entity->all();
    }

    public function allWithTrashed(): Collection
    {
        return $this->entity->withTrashed()->get();
    }

    public function delete($id): ?bool
    {
        return $this->find($id)->delete();
    }

    public function findBySlug(string $slug): ?Model
    {
        return $this->entity->withoutGlobalScopes()->where('slug', $slug)->first();
    }

    public function forceDelete($id): ?bool
    {
        return $this->find($id)->forceDelete();
    }

    public function firstOrCreate(array $data): Model
    {
        return $this->entity->firstOrCreate($data);
    }

    public function model(): Model
    {
        return $this->entity;
    }

    public function updateContent($data): Model
    {
        $model = $this->entity->findOrFail($data->id);
        $model->touch();
        
        return $model;
    }

    /**
     * Generic data filtering for collections/queries.
     */
    public function filterData(array $input, Builder $query): LengthAwarePaginator
    {
        $search  = $input['search'] ?? null;
        $size    = $input['size'] ?? 10;
        $orderBy = $input['orderBy'] ?? [];
        
        $columns = $this->entity->getFillable();

        foreach ($orderBy as $key => $direction) {
            $query->orderBy($key, $direction);
        }

        if ($search) {
            $query->where(function (Builder $q) use ($search, $columns) {
                foreach ($columns as $column) {
                    $q->orWhere($column, 'like', '%' . $search . '%');
                }
            });
        }

        return $query->paginate($size)->appends($input);
    }

    public function convertToClassName(string $prefix, string $string): string
    {
        $name = collect(explode('_', $string))
            ->map(fn($item) => ucfirst($item))
            ->implode('');

        return "{$prefix}\\{$name}\\{$name}";
    }
}
