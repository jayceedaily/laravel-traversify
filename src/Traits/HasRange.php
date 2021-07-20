<?php
namespace Traversify\Traits;

use Illuminate\Database\Eloquent\Builder;
use RuntimeException;
use InvalidArgumentException;

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
        if(!$this->range || !$range) {

            return;
        }

        foreach($this->range as $rangeable) {

            if(in_array($rangeable, array_keys($range))) {

                $rangeables = explode('.', $rangeable);

                $this->createRangeQuery($query, $rangeables, $range[$rangeable]);
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
    private function createRangeQuery(Builder $query, Array $rangeables, String $value)
    {
        $rangeColumn = array_shift($rangeables);

        if(count($rangeables)) {

            $query->whereHas($rangeColumn, function($_query) use ($rangeables, $value) {

                $this->createRangeQuery($_query, $rangeables, $value);
            });

        } else {

            $query->where($rangeColumn, $value);
        }
    }
}
