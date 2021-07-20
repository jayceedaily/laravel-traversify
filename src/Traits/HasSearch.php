<?php
namespace Traversify\Traits;

use Exception;
use RuntimeException;
use InvalidArgumentException;
use Kirschbaum\PowerJoins\PowerJoins;
use Illuminate\Database\Eloquent\Builder;

trait HasSearch
{
    protected $hasSearchRelationshipDriver = 'PowerJoins';

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
    public function scopeSearch(Builder $query, String $keyword = '')
    {
        if(!$this->traversify['search']) {
            throw new Exception('No column configured to be searched');
        }

        if(empty($keyword)) {
            return;
        }

        foreach($this->traversify['search'] as $searchable) {

            if($this->hasSearchRelationshipDriver == 'PowerJoins') {

                $this->createPowerJoinSearchQuery($query, $searchable, $keyword);

            }

            if($this->hasSearchRelationshipDriver == 'Eloquent') {

                $searchables = explode('.', $searchable);

                $this->createEloquentSearchQuery($query, $searchables, $keyword );
            }
        }
    }

    private function createPowerJoinSearchQuery(Builder $query, String $searchable, String $keyword)
    {
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

        return $query;
    }

    /**
     * Generate search query using Eloquent relationships
     *
     * @param Builder $query
     * @param array $searchable
     * @param string $keyword
     * @return void
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    private function createEloquentSearchQuery(Builder $query, Array $searchable, String $keyword)
    {
        $searchColumn = array_shift($searchable);

        if(count($searchable)) {

            $query->orWhereHas($searchColumn, function($_query) use ($searchable, $keyword) {

                $_query->where(function($_query) use ($searchable, $keyword) {

                    $this->createEloquentSearchQuery($_query, $searchable, $keyword);
                });
            });

        } else {

            $query->orWhere(function($_query) use ($searchColumn, $keyword) {

                $keywords = explode(" ", $keyword);

                foreach ($keywords as $_keyword) {

                    $_query->orWhere($searchColumn, 'LIKE', "%$_keyword" );

                    $_query->orWhere($searchColumn, 'LIKE', "$_keyword%" );
                }
            });
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
