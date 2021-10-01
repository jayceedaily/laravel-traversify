<?php
namespace Traversify\Traits;

use Exception;
use RuntimeException;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Kirschbaum\PowerJoins\PowerJoins;
use Illuminate\Database\Eloquent\Builder;

trait HasSort
{
    use PowerJoins;

    private $joined = [];

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

            $model = new self;

            foreach($sortables as $relationship) {
                $model = $model->$relationship()->getRelated();
            }

            $tableName = $model->getTable();

            $query->orderBy("$tableName.$sortColumn", $sort[$sortable]);
        }

        else {

            $tableName = $this->getTable();

            $query->orderBy("$tableName.$sortColumn", $sort[$sortable]);
        }
    }
}
