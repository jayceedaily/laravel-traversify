# Traversify

Documentation:
Traversify uses query string in all of its transaction and requires
models to have prerequisite variables to only allow queries on 
selected Fields.

### Feature Request:
1. Non-eloquent version, using raw sql query
2. Add service providers


### Change Log:

2019-06-17

- Setup for packagist

2019-02-07

-    Added custom where statement

2019-02-01

-    Added debugging feature that shows executed sql query
- add `debug=true` on query string to **ONLY WORK ON DEBUG ENVIRONMENT**

- Unknown Date fixed
-    Changed '->' to '~' for relational queries

2019-01-23

-    Refactored getters from multiple __getXXX() to __getters() which does what multiple functions does before.



### Pre-requisites:

* **public static $searchables** = ['x', 'y', 'z', ...];
* **public static $filterables** = ['x', 'y', 'z', ...];
* **public static $orderables** = ['x', 'y', 'z', ...];
* **public static $rangables** = ['x', 'y', 'z', ...];

How to use:
-------------------------------------------------------------------------
1.) Implement Traversify as a trait in your Model.

Example:
```php
use Traversify\Traversify;

class MyModel {

use Traversify;

public static $searchables  = [...];
public static $filterables  = [...];
public static $orderables   = [...];
public static $rangables    = [...];

// Your code goes here...
}
```

-------------------------------------------------------------------------
2.) Traverse your Model in your Controller
```php
Example:
``
public function index(){

$x = new MyModel;

$data = $x->traverse();
// Your code goes here...
}
``
or

public function index(){

$data = (new MyModel)->traverse();
// Your code goes here... 

}
```
-------------------------------------------------------------------------
Features:
--------------------------------------------------------------------------
By default, Traversify uses paginate unless 'expose' is set to true
or take is used. In these cases, get is used instead.
--------------------------------------------------------------------------


Filter ---> :: URL [ www.example.com/filter[x]=1&filter[y]=2&filter[z]=3 ]
Return records that has either of the following values.
+ Single/Multiple Properties Capable

Search ---> :: URL [ www.example.com/search=keyword ]
Return records with the like of the given keyword

Order ---> :: URL [ www.example.com/order[a]=asc ]
Return records with column a ordered in ascending
+ Single/Multiple Properties Capable 

Range ---> :: URL [ www.example.com/range[x]=2000-01-01,2019-12-31 ]
Return records with x within 2001-01-01 to 2019-12-31
+ Single/Multiple Properties Capable 

Take ---> :: URL [ www.example.com/take=5 ]
Return 5 records. (not paginatable)

Expose ---> :: URL [ www.example.com/expose=true ]
Return all records (not paginatable)

Paginate ---> :: URL [ www.example.com/page=3&limit=20 ]
Return offset of 60 with 20 records

All properties can be set statically within the controller, note that this will not
honor request variable counterpart.

-------------------------------------------------------------
Create a bug:

You can induce a bug by setting the beetle variable to true.

Q: Why would you want a bug as a feature?
A: To make the laravel throw an error that expose the query laravel is using
which can then be analyzed if the query that is being run is correct

Example:

$foo = new Model;

$foo->beetle = true;

$foo->traverse();

-------------------------------------------------------------

---------------------------------------------------------------------------------------
Important: All features can be nested with one another, unless if conflicting request.
---------------------------------------------------------------------------------------

