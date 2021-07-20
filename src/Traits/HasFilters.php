<?php
namespace Traversify\Traits;

use Illuminate\Database\Eloquent\Builder;
use RuntimeException;
use InvalidArgumentException;

trait HasFilters
{
    /**
     * Initialize filters
     *
     * @param Builder $query
     * @param array $filter
     * @return void
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function scopeFilter(Builder $query, Array $filter = [])
    {
        if(!$this->filters || !$filter) {

            return;
        }

        foreach($this->filters as $filterable) {

            if(in_array($filterable, array_keys($filter))) {

                $filterables = \explode('.', $filterable);

                $this->createFilterQuery($query, $filterables, $filter[$filterable]);
            }
        }
    }

    /**
     * Generate filter query
     *
     * @param Builder $query
     * @param array $filterables
     * @param string $value
     * @return void
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    private function createFilterQuery(Builder $query, Array $filterables, String $value)
    {
        $filterColumn = array_shift($filterables);

        if(count($filterables)) {

            $query->whereHas($filterColumn, function($_query) use ($filterables, $value) {

                $this->createFilterQuery($_query, $filterables, $value);
            });

        } else {

            $query->where($filterColumn, $value);
        }
    }
}
