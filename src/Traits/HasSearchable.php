<?php
namespace Traversify\Traits;

use Illuminate\Database\Eloquent\Builder;
use RuntimeException;
use InvalidArgumentException;
use Kirschbaum\PowerJoins\PowerJoins;

trait HasSearchable
{
    use PowerJoins;
    /**
     * Initialize search query
     *
     * @param Builder $query
     * @param string|null $keyword
     * @return void
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function scopeSearch(Builder $query, String $keyword = null)
    {
        if(!$this->searchable || !$keyword) {
            return;
        }

        foreach($this->searchable as $searchable) {

            $searchables = explode('.', $searchable);

            $searchColumn = array_pop($searchables);

            if(count($searchables) >= 1) {

                $query->leftJoinRelationship(implode('.',$searchables));

                $relationshipTable = array_pop($searchables);

                $tableName = $this->$relationshipTable()->getRelated()->getTable();

                $query->orWhere("$tableName.$searchColumn", 'LIKE', "%$keyword%");

                $keywords = explode(" ", $keyword);

                foreach ($keywords as $_keyword) {

                    $query->orWhere("$tableName.$searchColumn", 'LIKE', "%$_keyword" );

                    $query->orWhere("$tableName.$searchColumn", 'LIKE', "$_keyword%" );
                }

            } else {
                $tableName = $this->getTable();

                $query->orWhere("$tableName.$searchColumn", 'LIKE', "%$keyword%");

                $keywords = explode(" ", $keyword);

                foreach ($keywords as $_keyword) {

                    $query->orWhere("$tableName.$searchColumn", 'LIKE', "%$_keyword" );

                    $query->orWhere("$tableName.$searchColumn", 'LIKE', "$_keyword%" );
                }
            }
        }
    }

    /**
     * Set searchable on runtime
     *
     * @param Illuminate\Database\Eloquent\Builder $query
     * @param mixed $params
     * @return void
     */
    public function scopeSetSearchable(Builder $query, Mixed $params)
    {
        if(!is_array($params)) {
            $params = [$params];
        }

        $searchables = array_merge($this->searchable, $params);

        $this->searchable = $searchables;
    }

    /**
     * Set searchable on runtime
     *
     * @param Illuminate\Database\Eloquent\Builder $query
     * @param mixed $params
     * @return void
     */
    public function scopeResetSearchable(Builder $query, Mixed $params)
    {
        $this->searchable = [];
    }
}
