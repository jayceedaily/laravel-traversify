<?php
namespace Traversify\Traits;

use Exception;
use RuntimeException;
use InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;

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
        if (!$this->traversify || ($this->traversify && !$filters = $this->traversify['filters'])) {
            throw new Exception('No column configured to be filtered');
        }

        if (empty($filter)) {
            return;
        }

        foreach($filters as $filterable) {

            if(in_array($filterable, array_keys($filter))) {

                $filterables = explode('.', $filterable);

                if ($this->hasSortRelationshipDriver == 'PowerJoin') {

                    throw new Exception('PowerJoin has not been implemented');

                } else {

                    $this->createFilterQuery($query, $filterables, $filter[$filterable]);

                }
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
