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
        /**
         * Use traversify via relationship chains
         */
        Builder::macro('traversify', function($request){

            $model = $this->getModel();

            if($model instanceOf Traversable) {

                return Traversify::traverse($this, $request);
            }

            throw new Exception($model::class . " does not implement traversify.");
        });
    }
}
