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

        $keyName = $this->getKeyName();

        $model = new self;

        foreach($sortables as $relationship) {
            $model = $model->$relationship()->getRelated();
        }

        $keyName = $model->getKeyName();

        $tableName = $model->getTable();

        if (count($sortables) && !collect($query->getQuery()->joins)->pluck('table')->contains($tableName)) {

            $tableName = count($sortables) === 1 ? strtolower($sortables[0]) : $tableName;

            $query->leftJoinRelationship(implode('.', $sortables), $tableName);
        }

        $sortColumnAlias = "sort_column_${tableName}_${sortColumn}";

        if(!$query->getQuery()->columns) {
            $query->select('*');
        }

        $query->selectRaw("CONCAT($tableName.$sortColumn, ';',$tableName.$keyName) as $sortColumnAlias");

        $query->orderBy($sortColumnAlias, $sort[$sortable]);
    }
}
