<?php

namespace Traversify;

use Traversify\Traits\HasSort;
use Traversify\Traits\HasRange;
use Traversify\Traits\HasSearch;
use Traversify\Traits\HasFilters;
use Traversify\Traits\HasAutoload;
use Illuminate\Database\Eloquent\Builder;

trait Traversify
{
    use HasFilters, HasRange, HasSearch, HasSort, HasAutoload;

    /**
     * All-in-one solution to create indexed endpoints fast
     * Out of the box support:
     * Search, sort, filter, load, range
     *
     * @param mixed $query
     * @param mixed $request
     * @return mixed
     */
    public function scopeTraversify(Builder $query, $request)
    {
        if( $request->has('search') &&
            is_string($request->search)) {

            $query->search($request->search);
        }

        if( $request->has('filter') &&
            is_array($request->filter)) {

            $query->filter($request->filter);
        }

        if( $request->has('sort') &&
            is_array($request->sort)) {

            $query->sort($request->sort);
        }

        if( $request->has('load') &&
            is_array($request->load)) {

            $query->autoload($request->load);
        }

        return $query;
    }
}
