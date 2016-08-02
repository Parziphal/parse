<?php

namespace Parziphal\Parse;

use Closure;
use ReflectionClass;
use Parse\ParseQuery;
use Parse\ParseObject;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Query
{
    protected static $operators = [
        '='  => 'equalTo',
        '!=' => 'notEqualTo',
        '>'  => 'greaterThan',
        '>=' => 'greaterThanOrEqualTo',
        '<'  => 'lessThan',
        '<=' => 'lessThanOrEqualTo',
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
        $queries = func_get_args();

        if (is_array($queries[0])) {
            $queries = $queries[0];
        }

        $q = $queries[0];
        $parseQueries = [];

        foreach ($queries as $query) {
            if ($query instanceof Closure) {
                $closure = $query;

                $query = new static($q->parseClassName, $q->fullClassName, $q->useMasterKey);

                $closure($query);

                $parseQueries[] = $query;
            } else {
                $parseQueries[] = $q->parseQueryFromQuery($query);
            }
        }

        $orQuery = new static(
            $queries[0]->parseClassName,
            $queries[0]->fullClassName,
            $queries[0]->useMasterKey
        );

        $orQuery->parseQuery = ParseQuery::orQueries($parseQueries);

        return $orQuery;
    }

    public function __construct($parseClassName, $fullClassName, $useMasterKey = false)
    {
        $this->parseClassName = $parseClassName;
        $this->parseQuery     = new ParseQuery($parseClassName);
        $this->fullClassName  = $fullClassName;
        $this->useMasterKey   = $useMasterKey;
    }

    /**
     * Instance calls are passed to the Parse Query.
     *
     * @return $this
     */
    public function __call($method, array $params)
    {
        $ret = call_user_func_array([$this->parseQuery, $method], $params);

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
        $queries = func_get_args();

        if (is_array($queries[0])) {
            $queries = $queries[0];
        }

        array_unshift($queries, $this);

        return static::orQueries($queries);
    }

    /**
     * ```
     * $query->where($key, '=', $value);
     * $query->where([$key => $value]);
     * $query->where($key, $value);
     * ```
     *
     * @return $this
     */
    public function where($key, $operator = null, $value = null)
    {
        if (is_array($key)) {
            $where = $key;

            foreach ($where as $key => $value) {
                if ($value instanceof ObjectModel) {
                    $value = $value->getParseObject();
                }

                $this->parseQuery->equalTo($key, $value);
            }
        } elseif (func_num_args() == 2) {
            if ($operator instanceof ObjectModel) {
                $operator = $operator->getParseObject();
            }

            $this->parseQuery->equalTo($key, $operator);
        } else {
            if (!array_key_exists($operator, self::$operators)) {
                throw new Exception("Invalid operator: " . $operator);
            }

            call_user_func([$this->parseQuery, self::$operators[$operator]], $key, $value);
        }

        return $this;
    }

    /**
     * Find a record by Object ID.
     *
     * @param string $objectId
     * @param mixed  $selectKeys
     *
     * @return ObjectModel|null
     */
    public function find($objectId, $selectKeys = null)
    {
        $this->parseQuery->equalTo('objectId', $objectId);

        return $this->first($selectKeys);
    }

    /**
     * Find a record by Object ID or throw an
     * exception otherwise.
     *
     * @param string $objectId
     * @param mixed  $selectKeys
     *
     * @return ObjectModel
     *
     * @throws ModelNotFoundException
     */
    public function findOrFail($objectId, $selectKeys = null)
    {
        $this->parseQuery->equalTo('objectId', $objectId);

        return $this->firstOrFail($selectKeys);
    }

    /**
     * Find a record by Object ID or return a new
     * instance otherwise.
     *
     * @param string $objectId
     * @param mixed  $selectKeys
     *
     * @return ObjectModel
     */
    public function findOrNew($objectId, $selectKeys = null)
    {
        $record = $this->find($objectId, $selectKeys);

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
     * @return ObjectModel|null
     */
    public function first($selectKeys = null)
    {
        if ($selectKeys) {
            $this->parseQuery->select($selectKeys);
        }

        $data = $this->parseQuery->first($this->useMasterKey);

        if ($data) {
            return $this->createModel($data);
        }
    }

    /**
     * Get the first record that matches the query
     * or throw an exception otherwise.
     *
     * @param string $objectId
     * @param mixed  $selectKeys
     *
     * @return ObjectModel
     *
     * @throws ModelNotFoundException
     */
    public function firstOrFail($selectKeys = null)
    {
        $first = $this->first($selectKeys);

        if (!$first) {
            $e = new ModelNotFoundException();

            $e->setModel($this->fullClassName);

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
     * @return ObjectModel
     */
    public function firstOrNew(array $data)
    {
        $record = $this->where($data)->first();

        if ($record) {
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
     * @return ObjectModel
     */
    public function firstOrCreate(array $data)
    {
        $record = $this->firstOrNew($data);

        if (!$record->id) {
            $record->save();
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
            $this->select($selectKeys);
        }

        return $this->createModels($this->parseQuery->find($this->useMasterKey));
    }

    /**
     * Allow to pass instances of either Query or ParseQuery.
     *
     * @param string           $key
     * @param Query|ParseQuery $query
     *
     * @return $this
     */
    public function matchesQuery($key, $query)
    {
        $this->parseQuery->matchesQuery($key, $this->parseQueryFromQuery($query));

        return $this;
    }

    /**
     * Allow to pass instances of either Query or ParseQuery.
     *
     * @param string           $key
     * @param Query|ParseQuery $query
     *
     * @return $this
     */
    public function doesNotMatchQuery($key, $query)
    {
        $this->parseQuery->doesNotMatchQuery($key, $this->parseQueryFromQuery($query));

        return $this;
    }

    /**
     * Allow to pass instances of either Query or ParseQuery.
     *
     * @param string           $key
     * @param Query|ParseQuery $query
     *
     * @return $this
     */
    public function matchesKeyInQuery($key, $queryKey, $query)
    {
        $this->parseQuery->matchesKeyInQuery($key, $queryKey, $this->parseQueryFromQuery($query));

        return $this;
    }

    /**
     * Allow to pass instances of either Query or ParseQuery.
     *
     * @param string           $key
     * @param Query|ParseQuery $query
     *
     * @return $this
     */
    public function doesNotMatchKeyInQuery($key, $queryKey, $query)
    {
        $this->parseQuery->doesNotMatchKeyInQuery($key, $queryKey, $this->parseQueryFromQuery($query));

        return $this;
    }

    public function count()
    {
        return $this->parseQuery->count($this->useMasterKey);
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
        if (is_string($keys)) {
            $keys = func_get_args();
        }

        $this->includeKeys = array_merge($this->includeKeys, $keys);

        $this->parseQuery->includeKey($keys);

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

        $model = new $className($data, $this->useMasterKey);

        if ($this->includeKeys) {
            // Force model to load into its relations array the eager-loaded
            // relations. If not, non-loaded relations won't be included when
            // calling toArray() on the model.
            foreach ($this->includeKeys as $key) {
                $model->getRelationValue($key);
            }
        }

        return $model;
    }

    /**
     * @param array $objects ParseObject[]
     *
     * @return Collection
     */
    protected function createModels(array $objects)
    {
        $className = $this->fullClassName;
        $models    = [];

        foreach ($objects as $object) {
            $models[] = $this->createModel($object);
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
