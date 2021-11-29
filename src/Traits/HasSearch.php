<?php
namespace Traversify\Traits;

use Exception;
use RuntimeException;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Support\Facades\Log;
use Kirschbaum\PowerJoins\PowerJoins;
use Illuminate\Database\Eloquent\Builder;

trait HasSearch
{
    use PowerJoins;

    protected $like = 'LIKE';

    /**
     * Initialize search query
     *
     * @param Builder $query
     * @param string|null $keyword
     * @return void
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function scopeSearch(Builder $query, String $keyword = '')
    {
        if (!$searches = $this->search) {
            throw new Exception('No column configured to be searched');
        }

        if (empty($keyword)) {
            return;
        }

        $key = $this->connection ?: config('database.default');

        if(config('database.connections.' . $key . '.driver') == 'pgsql') {

            $this->like = 'ILIKE';
        }

        $columns = [];

        foreach($searches as $searchable) {

            $searchables = explode('.', $searchable);

            $searchColumn = array_pop($searchables);

            if (count($searchables)) {

                $model = new self;

                foreach($searchables as $relationship) {
                    $model = $model->$relationship()->getRelated();
                }

                $tableName = $model->getTable();

                $alias = $tableName;

                if(!collect($query->getQuery()->joins)->pluck('table')->contains($tableName)) {

                    $alias = count($searchables) === 1 ? strtolower($searchables[0]) : $tableName;

                    $query->leftJoinRelationship(implode('.', $searchables), $alias);
                }

                array_push($columns, "$alias.$searchColumn");

            } else {

                $tableName = $this->getTable();

                array_push($columns, "$tableName.$searchColumn");
            }
        }

        $columns = implode(', ', $columns);

        return $query->whereRaw("CONCAT_WS(' ', {$columns}) {$this->like} ?", "%{$keyword}%");
    }
}
