<?php

namespace Parziphal\Parse;

use DateTime;
use Traversable;
use LogicException;
use Parse\ParseFile;
use JsonSerializable;
use Parse\ParseObject;
use Illuminate\Support\Arr;
use Parse\Internal\Encodable;
use Illuminate\Support\Pluralizer;
use Parziphal\Parse\Relations\HasMany;
use Parziphal\Parse\Relations\Relation;
use Parziphal\Parse\Relations\BelongsTo;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use Parziphal\Parse\Relations\HasManyArray;
use Parziphal\Parse\Relations\BelongsToMany;

abstract class ObjectModel implements Arrayable, Jsonable, JsonSerializable
{
    protected static $parseClassName;

    /**
     * Defines the default value of $useMasterKey througout all class methods,
     * such as `query`, `create`, `all`, `__construct`, and `__callStatic`.
     *
     * @var bool
     */
    protected static $defaultUseMasterKey = false;

    protected $parseObject;

    protected $relations = [];

    protected $useMasterKey;

    public static function shortName()
    {
        return substr(static::class, strrpos(static::class, '\\') + 1);
    }

    public static function parseClassName()
    {
        return static::$parseClassName ?: static::shortName();
    }

    public static function create($data, $useMasterKey = null)
    {
        if ($useMasterKey === null) {
            $useMasterKey = static::$defaultUseMasterKey;
        }

        $model = new static($data, $useMasterKey);

        $model->save();

        return $model;
    }

    /**
     * Create a new query for this class.
     *
     * @param  bool  $useMasterKey
     * @return Query
     */
    public static function query($useMasterKey = null)
    {
        if ($useMasterKey === null) {
            $useMasterKey = static::$defaultUseMasterKey;
        }

        return new Query(static::parseClassName(), static::class, $useMasterKey);
    }

    public static function all($useMasterKey = null)
    {
        if ($useMasterKey === null) {
            $useMasterKey = static::$defaultUseMasterKey;
        }

        return static::query($useMasterKey)->get();
    }

    /**
     * Set the default value for defaultUseMasterKey. This is intended to be used
     * as a global configuration, hence the value is set to "self" and not to "static".
     *
     * @param bool $value
     */
    public static function setDefaultUseMasterKey($value)
    {
        self::$defaultUseMasterKey = (bool)$value;
    }

    /**
     * Static calls are passed to a new query.
     *
     * @return mixed
     */
    public static function __callStatic($method, array $params)
    {
        $query = static::query(static::$defaultUseMasterKey);

        return call_user_func_array([$query, $method], $params);
    }

    /**
     * @param ParseObject|array  $data
     * @param bool               $useMasterKey
     */
    public function __construct($data = null, $useMasterKey = null)
    {
        if ($data instanceof ParseObject) {
            $this->parseObject = $data;
        } else {
            $this->parseObject = new ParseObject(static::parseClassName());

            if (is_array($data)) {
                $this->fill($data);
            }
        }

        $this->useMasterKey = $useMasterKey !== null ? $useMasterKey : static::$defaultUseMasterKey;
    }

    public function __get($key)
    {
        return $this->get($key);
    }

    public function __set($key, $value)
    {
        return $this->set($key, $value);
    }

    public function __isset($key)
    {
        return $this->parseObject->has($key);
    }

    /**
     * Instance calls are passed to the Parse Object.
     *
     * @return mixed
     */
    public function __call($method, array $params)
    {
        $ret = call_user_func_array([$this->parseObject, $method], $params);

        if ($ret === $this->parseObject) {
            return $this;
        }

        return $ret;
    }

    public function __clone()
    {
        $this->parseObject = clone $this->parseObject;
    }

    public function useMasterKey($value)
    {
        $this->useMasterKey = (bool)$value;

        return $this;
    }

    public function set($key, $value)
    {
        if (is_array($value)) {
            if (Arr::isAssoc($value)) {
                $this->parseObject->setAssociativeArray($key, $value);
            } else {
                $this->parseObject->setArray($key, $value);
            }
        } elseif ($value instanceof ObjectModel) {
            $this->parseObject->set($key, $value->parseObject);
        } else {
            $this->parseObject->set($key, $value);
        }

        return $this;
    }

    public function get($key)
    {
        if ($key == 'id') {
            return $this->id();
        }

        if ($this->isRelation($key)) {
            return $this->getRelationValue($key);
        }

        $value = $this->parseObject->get($key);

        return $value;
    }

    public function getRelationValue($key)
    {
        if ($this->relationLoaded($key)) {
            return $this->relations[$key];
        }

        if ($this->isRelation($key)) {
            return $this->getRelationshipFromMethod($key);
        }
    }

    public function relationLoaded($key)
    {
        return array_key_exists($key, $this->relations);
    }

    public function setRelation($relation, $value)
    {
        $this->relations[$relation] = $value;

        return $this;
    }

    public function isRelation($name)
    {
        return method_exists($this, $name);
    }

    public function id()
    {
        return $this->parseObject->getObjectId();
    }

    public function save()
    {
        $this->parseObject->save($this->useMasterKey);
    }

    public function update(array $data)
    {
        $this->fill($data)->save();
    }

    /**
     * @return $this
     */
    public function fill(array $data)
    {
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }

        return $this;
    }

    /**
     * ParseObject::add()'s second parameter
     * must be an array. This allows to pass
     * non-array values.
     *
     * @param string  $key
     * @param mixed   $value
     *
     * @return $this
     */
    public function add($key, $value)
    {
        if (!is_array($value)) {
            $value = [ $value ];
        }

        $this->parseObject->add($key, $value);

        return $this;
    }

    /**
     * ParseObject::addUnique()'s second parameter
     * must be an array. This allows to pass
     * non-array values.
     *
     * @param string  $key
     * @param mixed   $value
     *
     * @return $this
     */
    public function addUnique($key, $value)
    {
        if (!is_array($value)) {
            $value = [ $value ];
        }

        $this->parseObject->addUnique($key, $value);

        return $this;
    }

    public function toArray()
    {
        $array = $this->parseObjectToArray($this->parseObject);

        $relations = array_diff_key($this->relations, $array);

        if ($relations) {
          foreach ($this->relations as $name => $relation) {
              if ($relation instanceof Collection) {
                  $coll = [];

                  foreach ($relation as $object) {
                      $coll[] = $object->toArray();
                  }

                  $array[$name] = $coll;
              } else {
                  $array[$name] = $relation->toArray();
              }
          }
        }

        return $array;
    }

    /**
     * @return array
     */
    public function parseObjectToArray(ParseObject $object)
    {
        $array = $object->getAllKeys();
        $array['objectId']  = $object->getObjectId();

        $createdAt = $object->getCreatedAt();
        if ($createdAt) {
            $array['createdAt'] = $this->dateToString($createdAt);
        }

        $updatedAt = $object->getUpdatedAt();
        if ($updatedAt) {
            $array['updatedAt'] = $this->dateToString($updatedAt);
        }

        if ($object->getACL()) {
            $array['ACL'] = $object->getACL()->_encode();
        }

        foreach ($array as $key => $value) {
            if ($value instanceof ParseObject) {
                if (
                    $value->getClassName() == $this->parseObject->getClassName() &&
                    $value->getObjectId() == $this->parseObject->getObjectId()
                ) {
                    // If a key points to this parent object, we will skip it to avoid
                    // infinite recursion.
                } elseif ($value->isDataAvailable()) {
                    $array[$key] = $this->parseObjectToArray($value);
                }
            } elseif ($value instanceof ParseFile) {
                $array[$key] = $value->_encode();
            }
        }

        return $array;
    }

    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Formats a DateTime object the way it is returned from Parse Server.
     *
     * @return string
     */
    protected function dateToString(DateTime $date)
    {
        return $date->format('Y-m-d\TH:i:s.' . substr($date->format('u'), 0, 3) . '\Z');
    }

    /**
     * @param string  $key
     *
     * @return static
     */
    protected function getRelation($key)
    {
        return $this->relations[$key];
    }

    protected function hasRelation($key)
    {
        return isset($this->relations[$key]);
    }

    /**
     * Get the ParseObject for the current model.
     *
     * @return ParseObject
     */
    public function getParseObject()
    {
        return $this->parseObject;
    }

    /**
     * This object will have an array with references to many other objects.
     *
     * @param  string  $otherClass  The other object's class
     * @param  string  $key         The key under which the array will be stored
     * @return BelongsToMany
     */
    protected function belongsToMany($otherClass, $key = null)
    {
        if (!$key) {
            $key = $this->getCallerFunctionName();
        }

        return new BelongsToMany($otherClass, $key, $this);
    }

    protected function belongsTo($otherClass, $key = null)
    {
        if (!$key) {
            $key = $this->getCallerFunctionName();
        }

        return new BelongsTo($otherClass, $key, $this);
    }

    protected function hasMany($otherClass, $key = null)
    {
        if (!$key) {
            $key = lcfirst(static::parseClassName());
        }

        return new HasMany($otherClass::query(), $this, $key);
    }

    /**
     * This is the reverse relation of belongsToMany. Children are expected to
     * store the parents' keys in an array. By default, the $foreignKey is
     * expected to be the plural of the parent object's name.
     *
     * @param  string  $otherClass
     * @param  string  $foreignKey
     * @return HasManyArray
     */
    protected function hasManyArray($otherClass, $foreignKey = null)
    {
        if (!$foreignKey) {
            $foreignKey = Pluralizer::plural(lcfirst(static::parseClassName()));
        }

        return new HasManyArray($otherClass::query(), $this, $foreignKey);
    }

    protected function getRelationshipFromMethod($method)
    {
        $relations = $this->$method();

        if (!$relations instanceof Relation) {
            throw new LogicException('Relationship method must return an object of type '
                .'Parziphal\Parse\Relations\Relation');
        }

        $results = $relations->getResults();

        $this->setRelation($method, $results);

        return $results;
    }

    protected function getCallerFunctionName()
    {
        return debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2]['function'];
    }
}
