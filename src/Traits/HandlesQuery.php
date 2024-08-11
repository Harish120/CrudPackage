<?php

namespace Harry\CrudPackage\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait HandlesQuery
{
    /**
     * Initialize the model with query, filters, and sorting.
     *
     * @param Builder $query
     * @param array $params
     * @return Builder
     */
    public function initializeQuery(Builder $query, array $params): Builder
    {
        // Apply filters
        if (!empty($params['filters']) && is_array($params['filters'])) {
            $filters = $params['filters'];

            foreach ($filters as $key => $value) {
                if (is_array($value) && isset($value['scope'])) {
                    $scopeMethod = $value['scope'];
                    if (method_exists($query->getModel(), $scopeMethod)) {
                        $query = $query->scopes([$scopeMethod => $value['value']]);
                    }
                } else {
                    $query->where($key, $value);
                }
            }
        }

        // Apply sorting
        if (!empty($params['sortBy'])) {
            $sortBy = $params['sortBy'];
            $descending = $params['descending'] ?? false;
            $direction = $descending ? 'desc' : 'asc';
            $query->orderBy($sortBy, $direction);
        }

        return $query;
    }
}
