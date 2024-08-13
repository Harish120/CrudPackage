<?php

namespace Harryes\CrudPackage\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait HandlesQuery
{
    /**
     * Initialize the model with query, filters, and sorting.
     *
     * @param Builder $query
     * @param array $params
     * @return Builder
     */
    public static function initializeQuery(): Builder
    {
        $filters = json_decode(request()->query('filters', '{}'), true);
        $sortBy = request()->input('sortBy');
        $descending = request()->input('descending', false);

        $model = static::query();

        // Apply filters
        foreach ($filters as $filter => $value) {
            $method = ucfirst(Str::camel($filter));
            if (method_exists(static::class, 'scope' . $method)) {
                $model = $model->scope($filter, $value);
            }  elseif (method_exists($model, $filter)) {
                $model = $model->{$filter}($value);
            }
        }

        // Apply sorting
        if(is_string($sortBy) && $sortBy !== 'null') {
            $model->orderBy($sortBy, $descending ? 'desc' : 'asc');
        } else {
            $model->latest();
        }

        return $model;
    }
}

