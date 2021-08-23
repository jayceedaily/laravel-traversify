<?php
namespace Traversify\Traits;

use Exception;
use RuntimeException;
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

        // TODO The scope where query is cancelling powerjoi queries
        // $query->where(function($query) use($searches, $keyword){

            foreach($searches as $searchable) {

                $this->createPowerJoinSearchQuery($query, $searchable, $keyword);

            }
        // });

    }

    private function createPowerJoinSearchQuery(Builder $query, String $searchable, String $keyword)
    {
        $searchables = explode('.', $searchable);

        $searchColumn = array_pop($searchables);

        if (count($searchables)) {


            $query->leftJoinRelationship(implode('.',$searchables));

            $relationshipTable = array_pop($searchables);


            $tableName = $this->$relationshipTable()->getRelated()->getTable();

            $keywords = explode(" ", $keyword);

            foreach ($keywords as $_keyword) {

                $query->orWhere("$tableName.$searchColumn", $this->like, "%$_keyword%" );

            }

        } else {

            $tableName = $this->getTable();

            $keywords = explode(" ", $keyword);

            foreach ($keywords as $_keyword) {
                $query->orWhere("$tableName.$searchColumn", $this->like, "%$_keyword%" );

            }
        }

        $query;
    }
}
