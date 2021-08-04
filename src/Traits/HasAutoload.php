<?php
namespace Traversify\Traits;

use Exception;
use RuntimeException;
use InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;

trait HasAutoload
{
    public function scopeAutoload(Builder $query, Array $load = [])
    {
        if (!$this->traversify || ($this->traversify && ! $autoloads = $this->traversify['autoload'])) {
            throw new Exception('No column configured to be autoloaded');
        }

        if (empty($load)) {
            return;
        }

        foreach($autoloads as $autoload) {

            if(in_array($autoload, array_values($load))) {

                $query->with($autoload);
            }
        }
    }
}
