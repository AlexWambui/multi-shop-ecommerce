<?php

namespace App\Concerns;

use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Converts Laravel paginator into a consistent structure for Inertia.js
 * 
 * PROBLEM: When you pass a paginator to Inertia, pagination metadata gets lost.
 * SOLUTION: This helper extracts the pagination data into a clean structure.
 * 
 * WHAT IT RETURNS:
 * {
 *     data: [...],      // Your actual items (transformed if resource provided)
 *     links: [...],     // Pagination navigation links (prev, next, page numbers)
 *     meta: {           // Pagination metadata
 *         current_page: 1,
 *         last_page: 5,
 *         per_page: 10,
 *         total: 50
 *     }
 * }
 */
class AqilifyPaginator
{
    /**
     * Transform a paginator for Inertia.
     * 
     * @param LengthAwarePaginator $paginator
     * @param string|null $resourceClass  Optional Laravel resource class (must have collection() method)
     * 
     * @return array
     * 
     * @example
     * // Without resource (raw data)
     * AqilifyPaginator::make($products)
     * 
     * // With resource (transformed data)
     * AqilifyPaginator::make($products, ProductCardResource::class)
     */
    public static function make(LengthAwarePaginator $paginator, $resourceClass = null)
    {
        $hasResource = $resourceClass && method_exists($resourceClass, 'collection');
        
        return [
            'data' => $hasResource 
                ? $resourceClass::collection($paginator->items())->toArray(request())
                : $paginator->items(),
            'links' => $paginator->linkCollection(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        ];
    }
}