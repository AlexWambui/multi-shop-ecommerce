<?php

use App\Concerns\AqilifyPaginator;
use Illuminate\Pagination\LengthAwarePaginator;

if (!function_exists('aqilify_paginate')) {
    function aqilify_paginate(LengthAwarePaginator $paginator, ?string $resourceClass = null)
    {
        return AqilifyPaginator::make($paginator, $resourceClass);
    }
}