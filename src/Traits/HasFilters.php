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
        if (!$filters = $this->filters) {
            throw new Exception('No column configured to be filtered');
        }

        if (empty($filter)) {
            return;
        }

        foreach($filters as $filterable) {

            if(in_array($filterable, array_keys($filter))) {

                $filterables = explode('.', $filterable);

                $value = is_array($filter[$filterable]) ? $filter[$filterable] : [$filter[$filterable]];

                $this->createFilterQuery($query, $filterables, $value);
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
    private function createFilterQuery(Builder $query, Array $filterable, Array $value)
    {
        $filterColumn = array_pop($filterable);

        if (count($filterable)) {

            $query->leftJoinRelationship(implode('.',$filterable));

            $relationshipTable = array_pop($filterable);

            $tableName = $this->$relationshipTable()->getRelated()->getTable();

            $query->whereIn("$tableName.$filterColumn", $value);

        } else {

            $tableName = $this->getTable();

            $query->whereIn("$tableName.$filterColumn", $value);
        }
    }
}
