<?php
namespace Traversify\Traits;

use Exception;
use RuntimeException;
use InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;

trait HasAutoload
{
    public function scopeAutoload(Builder $query, Array $loader = [])
    {
        if (! $autoloads = $this->traversify['autoload']) {
            throw new Exception('No column configured to be autoloaded');
        }

        if (empty($loader)) {
            return;
        }

        foreach($autoloads as $load) {

            if(in_array($load, array_values($loader))) {

                $query->with($load);
            }
        }
    }
}
