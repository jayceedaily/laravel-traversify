<?php
namespace Traversify\Traits;

use Exception;
use RuntimeException;
use InvalidArgumentException;
use Kirschbaum\PowerJoins\PowerJoins;
use Illuminate\Database\Eloquent\Builder;

trait HasSort
{
    protected $hasSortRelationshipDriver = 'PowerJoins';

    use PowerJoins;

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
        if(!$sorts = $this->traversify['sort']) {
            throw new Exception("No column configured to be sorted");
        }

        if(empty($sort)) {
            return;
        }

        foreach($sorts as $sortable) {

            if (in_array($sortable, array_keys($sort))) {

                $sortables = explode('.', $sortable);

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
    }
}
