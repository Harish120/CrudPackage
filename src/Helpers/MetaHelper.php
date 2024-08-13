<?php

namespace Harryes\CrudPackage\Helpers;

use Illuminate\Pagination\LengthAwarePaginator;

class MetaHelper
{
    /**
     * Generate pagination meta data.
     *
     * @param LengthAwarePaginator $paginator
     * @return array
     */
    public static function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'next_page_url' => $paginator->nextPageUrl(),
            'prev_page_url' => $paginator->previousPageUrl(),
        ];
    }
}
