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
     * List of tables already joined
     * on runtime. Referenced by operations
     * to avoid duplicate joining.
     *
     * @var array
     */
    private $joined = [];

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

                if(empty(array_intersect($this->joined, $searchables))) {

                    $query->leftJoinRelationship(implode('.', $searchables));

                    $this->joined = array_merge($this->joined, $searchables);
                }

                $model = new self;

                foreach($searchables as $relationship) {
                    $model = $model->$relationship()->getRelated();
                }

                $tableName = $model->getTable();

                array_push($columns, "$tableName.$searchColumn");

            } else {

                $tableName = $this->getTable();

                array_push($columns, "$tableName.$searchColumn");
            }
        }

        $columns = implode(', ', $columns);

        return $query->whereRaw("CONCAT_WS(' ', {$columns}) {$this->like} ?", "%{$keyword}%");
    }
}
