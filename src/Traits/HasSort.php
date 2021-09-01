<?php
namespace Traversify\Traits;

use Exception;
use RuntimeException;
use InvalidArgumentException;
use Kirschbaum\PowerJoins\PowerJoins;
use Illuminate\Database\Eloquent\Builder;

trait HasSort
{
    use PowerJoins;

    protected $sortConfig = 'sort';

    /**
     * Initialize sorts
     *
     * @param Builder $query
     * @param array $sort
     * @return void
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function scopeSort(Builder $query, Array $sort = [])
    {
        if(!$sorts = $this->sort) {
            throw new Exception("No column configured to be sorted");
        }

        if(empty($sort)) {
            return;
        }

        foreach($sorts as $sortable) {

            if (in_array($sortable, array_keys($sort)) && in_array(strtoupper($sort[$sortable]), ['ASC', 'DESC'])) {

                $sortables = explode('.', $sortable);

                $this->createPowerJoinSortQuery($query, $sortables, $sort);
            }
        }
    }

    /**
     *
     * @param Builder $query
     * @param mixed $sortable
     * @param mixed $sort
     * @return void
     * @throws InvalidArgumentException
     */
    public function createPowerJoinSortQuery(Builder $query, array $sortables, array $sort)
    {
        $sortable = implode('.', $sortables);

        $sortColumn = array_pop($sortables);

        if (count($sortables) >= 1) {

            $query->leftJoinRelationship(implode('.', $sortables));

            $relationshipTable = array_pop($sortables);

            $tableName = $this->$relationshipTable()->getRelated()->getTable();

            $query->orderBy("$tableName.$sortColumn", $sort[$sortable]);

        }

        else {

            $tableName = $this->getTable();

            $query->orderBy("$tableName.$sortColumn", $sort[$sortable]);
        }
    }
}
