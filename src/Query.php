<?php

namespace Illuminate\Parse;

use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Parse\ParseObject;
use Parse\ParseQuery;
use Traversable;

class Query
{
    const OPERATORS = [
        '=' => 'equalTo',
        '!=' => 'notEqualTo',
        '>' => 'greaterThan',
        '>=' => 'greaterThanOrEqualTo',
        '<' => 'lessThan',
        '<=' => 'lessThanOrEqualTo',
        'like' => 'regex'
    ];

    /**
     * @var array
     */
    protected $includeKeys = [];

    /**
     * @var ParseQuery
     */
    protected $parseQuery;

    /**
     * @var string
     */
    protected $fullClassName;

    /**
     * @var string
     */
    protected $parseClassName;

    /**
     * @var bool
     */
    protected $useMasterKey;

    public function __construct($parseClassName, $fullClassName, $useMasterKey = false)
    {
        $this->parseClassName = $parseClassName;
        $this->parseQuery = new ParseQuery($parseClassName);
        $this->fullClassName = $fullClassName;
        $this->useMasterKey = $useMasterKey;
    }

    /**
     * Pass Query, ParseQuery or Closure, as params or in an
     * array. If Closure is passed, a new Query will be passed
     * as parameter.
     * First element must be an instance of Query.
     *
     * ```
     * Query::orQueries($query, $parseQuery);
     * Query::orQueries([$query, $parseQuery]);
     * Query::orQueries($query, function(Query $query) { $query->where(...); });
     * ```
     *
     * @param mixed $queries
     *
     * @return static
     */
    public static function orQueries()
    {
        $queries = func_get_args ();

        if (is_array ($queries[0])) {
            $queries = $queries[0];
        }

        $q = $queries[0];

        $parseQueries = [];

        foreach ($queries as $query) {
            if ($query instanceof Closure) {
                $closure = $query;

                $query = new static($q->parseClassName, $q->fullClassName, $q->useMasterKey);

                $closure($query);
            }
            $parseQueries[] = $q->parseQueryFromQuery ($query);
        }

        $orQuery = new static(
            $queries[0]->parseClassName,
            $queries[0]->fullClassName,
            $queries[0]->useMasterKey
        );

        $orQuery->parseQuery = ParseQuery::orQueries ($parseQueries);

        return $orQuery;
    }

    /**
     * Instance calls are passed to the Parse Query.
     *
     * @param  string $method
     * @param  array $parameters
     * @return $this
     */
    public function __call($method, array $parameters)
    {
        if (Str::startsWith ($method, 'where')) {
            return $this->dynamicWhere ($method, $parameters);
        }

        $ret = call_user_func_array ([$this->parseQuery, $method], $parameters);
        if ($ret === $this->parseQuery) {
            return $this;
        }

        return $ret;
    }

    public function __clone()
    {
        $this->parseQuery = clone $this->parseQuery;
    }

    public function useMasterKey($value)
    {
        $this->useMasterKey = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getFullClassName()
    {
        return $this->fullClassName;
    }

    /**
     * @param mixed $queries
     *
     * @return static
     */
    public function orQuery()
    {
        $queries = func_get_args ();

        if (is_array ($queries[0])) {
            $queries = $queries[0];
        }

        array_unshift ($queries, $this);

        return static::orQueries ($queries);
    }

    /**
     * ```
     * $query->where($key, '=', $value);
     * $query->where([$key => $value]);
     * $query->where($key, $value);
     * ```
     *
     * @param $key
     * @param null $operator
     * @param null $value
     * @return $this
     * @throws Exception
     */
    public function where($key, $operator = null, $value = null)
    {
        if (is_array ($key)) {
            $where = $key;

            foreach ($where as $key => $value) {
                if ($value instanceof Model) {
                    $value = $value->getParseObject ();
                }

                $this->parseQuery->equalTo ($key, $value);
            }
        } else if ($key instanceof Closure) {
            return $key($this);
        } else {
            if (!array_key_exists ($operator, self::OPERATORS)) {
                throw new Exception("Invalid operator: " . $operator);
            }

            call_user_func ([$this, self::OPERATORS[$operator]], $key, $value);
        }

        return $this;
    }

    /**
     * Alias for containedIn.
     *
     * @param  string $key
     * @param  mixed $values
     *
     * @return $this
     */
    public function whereIn($key, $values)
    {
        return $this->containedIn ($key, $values);
    }

    /**
     * Alias for notContainedIn.
     *
     * @param  string $key
     * @param  mixed $values
     *
     * @return $this
     */
    public function whereNotIn($key, $values)
    {
        return $this->notContainedIn ($key, $values);
    }

    /**
     * @param string $key
     * @return $this
     */
    public function whereNull($key)
    {
        return $this->whereIn ($key, null);
    }

    /**
     * @param string $key
     * @return $this
     */
    public function whereNotNull($key)
    {
        return $this->whereNotIn ($key, null);
    }

    /**
     * Add a constraint for finding objects that contain the given key.
     *
     * @param string $key
     * @return $this
     */
    public function whereExists($key)
    {
        $this->parseQuery->exists($key);

        return $this;
    }

    /**
     * Add a constraint for finding objects that not contain the given key.
     *
     * @param string $key
     * @return $this
     */
    public function whereNotExists($key)
    {
        $this->parseQuery->doesNotExist ($key);

        return $this;
    }

    /**
     * ```
     * $query->orWhere($key, '=', $value);
     * $query->orWhere([$key => $value]);
     * $query->orWhere($key, $value);
     * ```
     *
     * @param $key
     * @param null $operator
     * @param null $value
     * @return $this
     * @throws Exception
     */
    public function orWhere($key, $operator = null, $value = null)
    {
        return $this->orQuery (function (Query $query) use ($key, $operator, $value) {
            $query->where ($key, $operator, $value);
        });
    }

    /**
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function regex($key, $value)
    {
        $this->parseQuery->regex ($key, $value);

        return $this;
    }

    /**
     * Add the without-trashed extension to the query.
     *
     * @return Query
     */
    public function withoutTrashed()
    {
        return $this->whereNull (Model::DELETED_AT);
    }

    /**
     * Add the only-trashed extension to the query
     *
     * @return Query
     */
    public function onlyTrashed()
    {
        return $this->whereNotNull (Model::DELETED_AT);
    }

    /**
     * Handles dynamic "where" clauses to the query.
     *
     * @param  string $method
     * @param  string $parameters
     * @return $this
     */
    public function dynamicWhere($method, $parameters)
    {
        $finder = substr ($method, 5);
        $segments = preg_split ('/(And|Or)(?=[A-Z])/', $finder, -1, PREG_SPLIT_DELIM_CAPTURE);

        // The connector variable will determine which connector will be used for the
        // query condition. We will change it as we come across new boolean values
        // in the dynamic method strings, which could contain a number of these.
        $connector = 'And';
        $index = 0;
        foreach ($segments as $segment) {
            // If the segment is not a boolean connector, we can assume it is a column's name
            // and we will add it to the query as a new constraint as a where clause, then
            // we can keep iterating through the dynamic method string's segments again.
            if ($segment != 'And' && $segment != 'Or' && $segment) {
                return $this->addDynamic ($segment, $connector, $parameters, $index);
                //$index++;
            }

            // Otherwise, we will store the connector so we know how the next where clause we
            // find in the query should be connected to the previous ones, meaning we will
            // have the proper boolean connector to connect the next where clause found.
            else if ($segment) {
                $connector = $segment;
            }
        }

        return $this;
    }

    /**
     * Add a single dynamic where clause statement to the query.
     *
     * @param  string $segment
     * @param  string $connector
     * @param  array $parameters
     * @param  int $index
     * @return $this
     */
    protected function addDynamic($segment, $connector, $parameters, $index)
    {
        // Once we have parsed out the columns and formatted the boolean operators we
        // are ready to add it to this query as a where clause just like any other
        // clause on the query. Then we'll increment the parameter index values.
        $bool = strtolower ($connector);

        return ($bool == 'or') ?
            $this->orWhere (Str::camel ($segment), '=', $parameters[$index]) :
            $this->where (Str::camel ($segment), '=', $parameters[$index]);
    }

    /**
     * Find a record by Object ID.
     *
     * @param string $objectId
     * @param mixed $selectKeys
     *
     * @return Model|null
     */
    public function find($objectId, $selectKeys = null)
    {
        $this->parseQuery->equalTo ('objectId', $objectId);

        return $this->first ($selectKeys);
    }

    /**
     * Find a record by Object ID or throw an
     * exception otherwise.
     *
     * @param string $objectId
     * @param mixed $selectKeys
     *
     * @return Model
     *
     * @throws ModelNotFoundException
     */
    public function findOrFail($objectId, $selectKeys = null)
    {
        $this->parseQuery->equalTo ('objectId', $objectId);

        return $this->firstOrFail ($selectKeys);
    }

    /**
     * Find a record by Object ID or return a new
     * instance otherwise.
     *
     * @param string $objectId
     * @param mixed $selectKeys
     *
     * @return Model
     */
    public function findOrNew($objectId, $selectKeys = null)
    {
        $record = $this->find ($objectId, $selectKeys);

        if (!$record) {
            $class = $this->fullClassName;

            $record = new $class(null, $this->useMasterKey);
        }

        return $record;
    }

    /**
     * Get the first record that matches the query.
     *
     * @param mixed $selectKeys
     *
     * @return Model|null
     */
    public function first($selectKeys = null)
    {
        if ($selectKeys) {
            $this->parseQuery->select ($selectKeys);
        }

        $data = $this->parseQuery->first ($this->useMasterKey);

        if ($data) {
            return $this->createModel ($data);
        }
    }

    /**
     * Get the first record that matches the query
     * or throw an exception otherwise.
     *
     * @param mixed $selectKeys
     *
     * @return Model
     *
     * @throws ModelNotFoundException
     */
    public function firstOrFail($selectKeys = null)
    {
        $first = $this->first ($selectKeys);

        if (!$first) {
            $e = new ModelNotFoundException();

            $e->setModel ($this->fullClassName);

            throw $e;
        }

        return $first;
    }

    /**
     * Get the first record that matches the query
     * or return a new instance otherwise.
     *
     * @param array $data
     *
     * @return Model
     */
    public function firstOrNew(array $data)
    {
        if (!is_null ($record = $this->where ($data)->first ())) {
            return $record;
        }

        $class = $this->fullClassName;

        return new $class($data, $this->useMasterKey);
    }

    /**
     * Get the first record that matches the query
     * or create it otherwise.
     *
     * @param array $data
     *
     * @return Model
     */
    public function firstOrCreate(array $data)
    {
        $record = $this->firstOrNew ($data);

        if (!$record->id) {
            $record->save ();
        }

        return $record;
    }

    /**
     * Executes the query and returns its results.
     *
     * @param string|string[] $selectKeys
     *
     * @return Collection
     */
    public function get($selectKeys = null)
    {
        if ($selectKeys) {
            $this->select ($selectKeys);
        }

        return $this->createModels ($this->parseQuery->find ($this->useMasterKey));
    }

    /**
     * Allow to pass instances of either Query or ParseQuery.
     *
     * @param string $key
     * @param Query|ParseQuery $query
     *
     * @return $this
     */
    public function matchesQuery($key, $query)
    {
        $this->parseQuery->matchesQuery ($key, $this->parseQueryFromQuery ($query));

        return $this;
    }

    /**
     * Allow to pass instances of either Query or ParseQuery.
     *
     * @param string $key
     * @param Query|ParseQuery $query
     *
     * @return $this
     */
    public function doesNotMatchQuery($key, $query)
    {
        $this->parseQuery->doesNotMatchQuery ($key, $this->parseQueryFromQuery ($query));

        return $this;
    }

    /**
     * Allow to pass instances of either Query or ParseQuery.
     *
     * @param string $key
     * @param Query|ParseQuery $query
     *
     * @return $this
     */
    public function matchesKeyInQuery($key, $queryKey, $query)
    {
        $this->parseQuery->matchesKeyInQuery ($key, $queryKey, $this->parseQueryFromQuery ($query));

        return $this;
    }

    /**
     * Allow to pass instances of either Query or ParseQuery.
     *
     * @param string $key
     * @param Query|ParseQuery $query
     *
     * @return $this
     */
    public function doesNotMatchKeyInQuery($key, $queryKey, $query)
    {
        $this->parseQuery->doesNotMatchKeyInQuery ($key, $queryKey, $this->parseQueryFromQuery ($query));

        return $this;
    }

    public function orderBy($key, $order = 1)
    {
        if ($order == 1) {
            $this->parseQuery->ascending ($key);
        } else {
            $this->parseQuery->descending ($key);
        }

        return $this;
    }

    /**
     * Add an "order by" clause for a timestamp to the query.
     *
     * @param  string $column
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function latest($column = Model::CREATED_AT)
    {
        return $this->orderBy ($column, 0);
    }

    /**
     * Add an "order by" clause for a timestamp to the query.
     *
     * @param  string $column
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function oldest($column = Model::CREATED_AT)
    {
        return $this->orderBy ($column, 1);
    }

    /**
     * Set the skip parameter as a query constraint.
     *
     * @param int $n Number of objects to skip from start of results.
     * @return $this
     */
    public function skip($n)
    {
        $this->parseQuery->skip ($n);
        return $this;
    }

    /**
     * Set the limit parameter as a query constraint.
     *
     * @param int $n Number of objects to return from the query.
     * @return $this
     */
    public function limit($n)
    {
        $this->parseQuery->limit ($n);
        return $this;
    }

    /**
     * Set the limit and offset for a given page.
     *
     * @param  int $page
     * @param  int $perPage
     * @return $this
     */
    public function forPage($page, $perPage = 50)
    {
        return $this->skip (($page - 1) * $perPage)->limit ($perPage);
    }

    /**
     * Get a paginator only supporting simple next and previous links.
     *
     * This is more efficient on larger data-sets, etc.
     *
     * @param  int $perPage
     * @param  array $columns
     * @param  string $pageName
     * @param  int|null $page
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function paginate($perPage = 15, $columns = null, $pageName = 'page', $page = null)
    {
        $page = $page ?: Paginator::resolveCurrentPage ($pageName);

        $this->skip (($page - 1) * $perPage)->limit ($perPage + 1);

        return new Paginator($this->get ($columns), $perPage, $page, [
            'path' => Paginator::resolveCurrentPath (),
            'pageName' => $pageName,
        ]);
    }

    /**
     * Chunk the results of the query.
     *
     * @param  int $count
     * @param  callable $callback
     * @return bool
     */
    public function chunk($count, callable $callback)
    {
        $page = 1;

        do {
            $results = $this->forPage ($page, $count)->get ();
            $countResults = $results->count ();

            if ($countResults == 0) {
                break;
            }

            // On each chunk result set, we will pass them to the callback and then let the
            // developer take care of everything within the callback, which allows us to
            // keep the memory low for spinning through large result sets for working.
            if (call_user_func ($callback, $results) === false) {
                return false;
            }

            $page++;
        } while ($countResults == $count);

        return true;
    }

    /**
     * Get an array with the values of a given column.
     *
     * @param  string $column
     * @param  string|null $key
     * @return \Illuminate\Support\Collection
     */
    public function pluck($column, $key = null)
    {
        $results = $this->get (is_null ($key) ? [$column] : [$column, $key]);

        // If the columns are qualified with a table or have an alias, we cannot use
        // those directly in the "pluck" operations since the results from the DB
        // are only keyed by the column itself. We'll strip the table out here.
        return $results->pluck (
            $this->stripTableForPluck ($column),
            $this->stripTableForPluck ($key)
        );
    }

    /**
     * Strip off the table name or alias from a column identifier.
     *
     * @param  string $column
     * @return string|null
     */
    protected function stripTableForPluck($column)
    {
        return is_null ($column) ? $column : last (preg_split ('~\.| ~', $column));
    }

    /**
     * Concatenate values of a given column as a string.
     *
     * @param  string $column
     * @param  string $glue
     * @return string
     */
    public function implode($column, $glue = '')
    {
        return $this->pluck ($column)->implode ($glue);
    }

    /**
     * Models are replaced for their ParseObjects. It also accepts any kind
     * of traversable variable.
     *
     * @param  string $key
     * @param  mixed $values
     *
     * @return $this
     */
    public function containedIn($key, $values)
    {
        if (!is_array ($values) && !$values instanceof Traversable) {
            $values = [$values];
        }

        foreach ($values as $k => $value) {
            if ($value instanceof Model) {
                $values[$k] = $value->getParseObject ();
            }
        }

        $this->parseQuery->containedIn ($key, $values);

        return $this;
    }

    /**
     * Models are replaced for their ParseObjects. It also accepts any kind
     * of traversable variable.
     *
     * @param  string $key
     * @param  mixed $values
     *
     * @return $this
     */
    public function notContainedIn($key, $values)
    {
        if (!is_array ($values) && !$values instanceof Traversable) {
            $values = [$values];
        }

        foreach ($values as $k => $value) {
            if ($value instanceof Model) {
                $values[$k] = $value->getParseObject ();
            }
        }

        $this->parseQuery->notContainedIn ($key, $values);

        return $this;
    }

    public function count()
    {
        return $this->parseQuery->count ($this->useMasterKey);
    }

    /**
     * Alias for ParseQuery's includeKey.
     *
     * @param string|array $keys
     *
     * @return $this
     */
    public function with($keys)
    {
        if (is_string ($keys)) {
            $keys = func_get_args ();
        }

        $this->includeKeys = array_merge ($this->includeKeys, $keys);

        $this->parseQuery->includeKey ($keys);

        return $this;
    }

    /**
     * @return ParseQuery
     */
    public function getParseQuery()
    {
        return $this->parseQuery;
    }

    protected function createModel(ParseObject $data)
    {
        $className = $this->fullClassName;

        return new $className($data, $this->useMasterKey);
    }

    /**
     * @param array $objects ParseObject[]
     *
     * @return Collection
     */
    protected function createModels(array $objects)
    {
        $models = [];
        foreach ($objects as $object) {
            $models[] = $this->createModel ($object);
        }

        return new Collection($models);
    }

    /**
     * @param  Query|ParseQuery $query
     *
     * @return ParseQuery
     */
    protected function parseQueryFromQuery($query)
    {
        return $query instanceof self ? $query->parseQuery : $query;
    }
}
