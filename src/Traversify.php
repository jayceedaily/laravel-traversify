<?php

namespace Traversify;

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

    public static function setTraverseLimit($limitNo)
    {
        $traversify         = new self;

        $traversify->limit  = $limitNo;

        return $traversify;
    }

    private function __queryBuilder()
    {
        $this->query = $this->query();

        $queries = ['Search','Filter','Range', 'Order', 'Custom'];

        self::loader($queries,'__query');

        if($this->expose || $this->take) {

            return ['data' => $this->query->take($this->take)->get()];

        } else {

            return $this->query->latest()->paginate($this->limit);

        }
    }

    private function __queryCustom()
    {
        if(!isset($this->custom)) return;

        $this->query->where($this->custom);
    }

    private function __querySearch()
    {
        if(!isset(self::$searchables) || is_null($this->search)) return;

        $keywords = explode(' ', $this->search);

        $this->query->where( function($query) use ($keywords)
        {
            foreach(self::$searchables ?: [] as $searchable):

                $_searchable = explode('~',$searchable);

                if(count($_searchable)>1):

                    $query->with($_searchable[0])->orWhereHas($_searchable[0], function($query) use ( $_searchable, $keywords)
                      {
                            foreach($keywords as $keyword):

                                $query->orWhere($_searchable[1],'LIKE','%'.$keyword.'%');
                            endforeach;

                      });
                  else:

					foreach($keywords as $keyword):

                        $query->orWhere($searchable,'LIKE','%'.$keyword.'%');
                    endforeach;

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
        foreach($getters as $getter)

            if(request()->has($getter))

                $this->$getter = request()->get($getter);
    }

    private function loader($loads, $prefix = '__')
    {
        foreach($loads as $load):

            self::{$prefix.$load}();

        endforeach;
    }
}
