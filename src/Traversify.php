<?php
/**
 * Traversify is a dynamic function that makes it easy to filter models
 * Developed by NASA using NOT Alien Technology
 * 
 * Documentation:
 * Traversify uses query string in all of its transaction and requires
 * models to have prerequisite variables to only allow queries on 
 * selected Fields.
 * 
 * Coming Soon:
 * -    Make traverse function static to not initialize an instance of Model
 * 
 * Change Log:
 * 
 * 2019-02-07
 * -    Added custom where statement
 * 
 * 2019-02-01
 * -    Added debugging feature that shows executed sql query
 *      + add `debug=true` on query string to
 *      # ONLY WORKS ON DEBUG ENVIRONMENT
 * 
 * Unknown Date
 * -    Changed '->' to '~' for relational queries
 * 
 *  2019-01-23
 * -    Refactored getters from multiple __getXXX() to __getters() which
 *      does what multiple functions does before.
 * 
 * 
 *
 * Pre-requisites:
 * -------------------------------------------------------------------------
 * These variables make sure that only specific columns are being queried.
 * -------------------------------------------------------------------------
 * public static $searchables   = ['x', 'y', 'z', ...];
 * public static $filterables   = ['x', 'y', 'z', ...];
 * public static $orderables    = ['x', 'y', 'z', ...];
 * public static $rangables     = ['x', 'y', 'z', ...];
 * ------------------------------------------------------------------------
 * How to use:
 * -------------------------------------------------------------------------
 * 1.) Implement Traversify as trait in your Model.
 * 
 * Example:
 * 
 * use App\Helpers\Traversify;
 * 
 * class MyModel {
 * 
 *      use Traversify;
 * 
 *      public static $searchables  = [...];
 *      public static $filterables  = [...];
 *      public static $orderables   = [...];
 *      public static $rangables    = [...];
 *
 *      // Your code goes here...
 * }
 * 
 * -------------------------------------------------------------------------
 * 2.) Traverse your Model in your Controller
 * 
 * Example:
 * 
 * public function index(){
 * 
 *      $x = new MyModel;
 * 
 *      $data = $x->traverse();
 *      // Your code goes here...
 * }
 *
 * or
 * 
 * public function index(){
 *      
 *      $data = (new MyModel)->traverse();
 *      // Your code goes here... 
 * s
 * }
 * 
 * -------------------------------------------------------------------------
 * Features:
 * --------------------------------------------------------------------------
 * By default, Traversify uses paginate unless 'expose' is set to true
 * or take is used. In these cases, get is used instead.
 * --------------------------------------------------------------------------
 * 
 * 
 * Filter ---> :: URL [ www.example.com/filter[x]=1&filter[y]=2&filter[z]=3 ]
 *  Return records that has either of the following values.
 *  + Single/Multiple Properties Capable
 * 
 * Search ---> :: URL [ www.example.com/search=keyword ]
 *  Return records with the like of the given keyword
 * 
 * Order ---> :: URL [ www.example.com/order[a]=asc ]
 *  Return records with column a ordered in ascending
 *  + Single/Multiple Properties Capable 
 * 
 * Range ---> :: URL [ www.example.com/range[x]=2000-01-01,2019-12-31 ]
 *  Return records with x within 2001-01-01 to 2019-12-31
 *  + Single/Multiple Properties Capable 
 * 
 * Take ---> :: URL [ www.example.com/take=5 ]
 *  Return 5 records. (not paginatable)
 * 
 * Expose ---> :: URL [ www.example.com/expose=true ]
 *  Return all records (not paginatable)
 * 
 * Paginate ---> :: URL [ www.example.com/page=3&limit=20 ]
 *  Return offset of 60 with 20 records
 * 
 * All properties can be set statically within the controller, note that this will not
 * honor request variable counterpart.
 * 
 * -------------------------------------------------------------
 * Create a bug:
 * 
 * You can induce a bug by setting the beetle variable to true.
 * 
 * Q: Why would you want a bug as a feature?
 * A: To make the laravel throw an error that expose the query laravel is using
 *    which can then be analyzed if the query that is being run is correct
 * 
 * Example:
 * 
 * $foo = new Model;
 * 
 * $foo->beetle = true;
 * 
 * $foo->traverse();
 * 
 * -------------------------------------------------------------
 * 
 * ---------------------------------------------------------------------------------------
 * Important: All features can be nested with one another, unless if conflicting request.
 * ---------------------------------------------------------------------------------------
 */
namespace App\Helpers;

trait Traversify
{
    public      $search;    # Search keyword (String)

    public      $filter;    # Query filters (Array)

    public      $custom;     # Custom queries

    public      $take;      # For limited show (Integer)

    public      $order;     # Order of list (Array)

    public      $range;     # Range of query (Array)

    public      $limit;     # Limit show - Paginate Prerequesit ()

    public      $expose;    # Returns all data (Uses get instead of paginate)
    
    public      $debug;     # For testing;

    private     $query;     # Query builder


    public static function traversify($custom = NULL)
    {
        $_traverse = new self;

        return $_traverse->traverse($custom);
    }


    public function traverse($custom = NULL)
    {
        $custom && $this->custom = $custom;
    
        $this->__getters();

        \DB::enableQueryLog();

        $result = $this->__queryBuilder();

        if($this->debug && env('APP_DEBUG')):

            return \DB::getQueryLog();

        else:

            return $result;

        endif;
    }

    private function __queryBuilder()
    {
        $this->query = $this->query();
        
        $queries = ['Search','Filter','Range', 'Order', 'Custom'];

        self::loader($queries,'__query');
       
        return ($this->expose || $this->take) ? ['data' => $this->query->take($this->take)->get()] : $this->query->paginate($this->limit);
    }

    private function __queryCustom()
    {
        if(!isset($this->custom)) return;

        $this->query->where($this->custom);
    }

    private function __querySearch()
    {
        if(!isset(self::$searchables)) return;

        $this->query->where( function($query)
        {
            foreach(self::$searchables ?: [] as $searchable):
                
                $_searchable = explode('~',$searchable);

                if(count($_searchable)>1):
                    
                    $query->with($_searchable[0])->orWhereHas($_searchable[0], function($query) use ( $_searchable )
                      {
                          $query->where($_searchable[1],'LIKE','%'.$this->search.'%');    
                      });
                  else:
                      $query->orWhere($searchable,'LIKE','%'.$this->search.'%');     
                  endif;

            endforeach;
        });
    }

    private function __queryFilter()
    {
        if(!isset(self::$filterables)) return;
        
        $this->query->where(function($query)
        {
            foreach($this->filter ?: [] as $attribute => $value):

                $value == 'null' && $value = null;

                if(in_array($attribute, self::$filterables)):

                    $relationship = explode('~', $attribute);

                    if(count($relationship) == 2):

                        if($value[0]=='!'):

                            $values = explode(',',substr($value,1));

                            $value == 'null' && $value = null;

                            $query->with($relationship[0])->whereHas($relationship[0],function($query) use ( $relationship, $values)
                            {
                                $query->whereNotIn($relationship[1],$values);                                    
                            });

                        else:
                
                            $values = explode(',',$value);

                            $query->with($relationship[0])->whereHas($relationship[0],function($query) use ( $relationship, $values, $value)
                            {
                                count($values) > 1 ? $query->whereIn($relationship[1],$values) : $query->where($relationship[1],$value);                            
                            });
                            
                        endif;

                    else:
                        if($value[0]=='!'):

                            $values = explode(',',substr($value,1));
        
                            $query->where(function($query) use ($attribute, $values)
                            {
                                $query->whereNotIn($attribute,$values);
                            });
                            
                        else:
                
                            $values = explode(',',$value);
                            
                            count($values) > 1 ? $query->whereIn($attribute,$values) : $query->where($attribute,$value);
    
                        endif;

                    endif;

                endif;
    
            endforeach;
        });
    }

    private function __queryRange()
    {
        if(!isset(self::$rangables)) return;

        $this->query->where(function($query)
        {
            foreach($this->range ?: [] as $attribute => $value):

                if(in_array($attribute, self::$rangables)):

                    $relationship = explode('~', $attribute);

                    if(count($relationship) == 2):

                        if($value[0]=='!'):

                            $values = explode(',',substr($value,1));

                            $query->with($relationship[0])->whereHas($relationship[0],function($query) use ( $relationship, $values)
                            {
                                $query->whereNotBetween($relationship[1],$values);                                    
                            });

                        else:
                
                            $values = explode(',',$value);

                            $query->with($relationship[0])->whereHas($relationship[0],function($query) use ( $relationship, $values, $value)
                            {
                               $query->whereBetween($relationship[1],$values);                            
                            });
                            
                        endif;

                    else:

                        if($value[0]=='!'):

                            $values = explode(',',substr($value,1));
        
                            $query->where(function($q) use ($attribute, $values)
                            {
                                $q->whereNotBetween($attribute,$values);
                            });
                            
                        else:

                            $values = explode(',',$value);
        
                            $query->whereBetween($attribute,$values);
        
                        endif;

                    endif;

                endif;

            endforeach;
        });
    }

    private function __queryOrder()
    {
        if(!isset(self::$orderables)) return;

        foreach($this->order ?: [] as $attribute => $value):

            if(in_array($attribute, self::$orderables)):
                
                $relationship = explode('~', $attribute);

                if(count($relationship) == 2):
                   
                    $this->query->with([$relationship[0] => function($query) use ( $relationship, $value)
                    {
                        $query->orderBy($relationship[1],$value); 
                    }]);

                else:

                    $this->query->orderBy($attribute, $value);

                endif;
                
            endif;

        endforeach;
    }

    private function __getters ( $getters = ['search', 'filter', 'take', 'order', 'range', 'limit', 'expose', 'debug'] )
    {
        foreach($getters as $getter):
            !$this->$getter && $this->$getter = request()->get($getter);
        endforeach;
    }

    private function loader($loads, $prefix = '__')
    {
        foreach($loads as $load):
            
            self::{$prefix.$load}();

        endforeach;
    }
}