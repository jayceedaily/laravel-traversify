<?php
namespace Traversify;

use Exception;
use Traversify\Traversify;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Builder;

class TraversifyServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        Builder::macro('traversify', function($request){

            $model = $this->getModel();

            $query = $this->getModel()::query();

            if($model instanceOf Traversable) {

                Traversify::traverse($query, $request);

                return $query;
            }

            throw new Exception($model::class . " does not implement traversify.");
        });
    }
}
