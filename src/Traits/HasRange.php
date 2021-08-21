<?php
namespace Traversify\Traits;

use Exception;
use RuntimeException;
use InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;

trait HasRange
{
    /**
     * Initialize ranges
     *
     * @param Builder $query
     * @param array $range
     * @return void
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function scopeRange(Builder $query, Array $range = [])
    {
        if (!$ranges = $this->range) {
            throw new Exception('No column configured to be ranged');
        }

        if (empty($range)) {
            return;
        }

        foreach($ranges as $rangeable) {

            if(in_array($rangeable, array_keys($range))) {

                $rangeables = explode('.', $rangeable);

                $this->createRangeQuery($query, $rangeables, $range[$rangeable]);
            }
        }
    }

    private function createRangeQuery(Builder $query, Array $rangeables, Array $value)
    {
        $rangeColumn = array_pop($rangeables);

        if (count($rangeables) >= 1) {

            $query->leftJoinRelationship(implode('.', $rangeables));

            $relationshipTable = array_pop($rangeables);

            $tableName = $this->$relationshipTable()->getRelated()->getTable();

            return $query->whereBetween("$tableName.$rangeColumn", $value);

        }

        else {

            $tableName = $this->getTable();

            return $query->whereBetween("$tableName.$rangeColumn", $value);
        }
    }
}
