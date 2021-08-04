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
        if (!$this->traversify || ($this->traversify && !$ranges = $this->traversify['range'])) {
            throw new Exception('No column configured to be ranged');
        }

        if (empty($range)) {
            return;
        }

        foreach($ranges as $rangeable) {

            if(in_array($rangeable, array_keys($range))) {

                if ($this->hasRangeRelationshipDriver == 'PowerJoin') {

                    throw new Exception('PowerJoin has not been implemented');

                } else {

                    $rangeables = explode('.', $rangeable);

                    $this->createRangeQuery($query, $rangeables, $range[$rangeable]);
                }
            }
        }
    }

    /**
     * Generate range query
     *
     * @param Builder $query
     * @param array $rangeables
     * @param string $value
     * @return void
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    private function createRangeQuery(Builder $query, Array $rangeables, Array $value)
    {
        $rangeColumn = array_shift($rangeables);

        if(count($rangeables)) {

            $query->whereHas($rangeColumn, function($_query) use ($rangeables, $value) {

                $this->createRangeQuery($_query, $rangeables, $value);
            });

        } else {

            $query->whereBetween($rangeColumn, $value);
        }
    }
}
