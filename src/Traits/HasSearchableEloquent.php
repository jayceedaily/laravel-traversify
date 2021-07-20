<?php
namespace Traversify\Traits;

use Illuminate\Database\Eloquent\Builder;
use RuntimeException;
use InvalidArgumentException;
use Kirschbaum\PowerJoins\PowerJoins;

trait HasSearchableEloquent
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

            $this->createSearchQuery($query, $searchables, $keyword);
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

    /**
     * Generate search query
     *
     * @param Builder $query
     * @param array $searchable
     * @param string $keyword
     * @return void
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    private function createSearchQuery(Builder $query, Array $searchable, String $keyword)
    {
        $searchColumn = array_shift($searchable);

        if(count($searchable)) {

            $query->orWhereHas($searchColumn, function($_query) use ($searchable, $keyword) {

                $_query->where(function($_query) use ($searchable, $keyword) {

                    $this->createSearchQuery($_query, $searchable, $keyword);
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
}
