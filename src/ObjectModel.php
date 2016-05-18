<?php

namespace Parziphal\Parse;

use Traversable;
use LogicException;
use Parse\ParseObject;
use ReflectionProperty;
use Illuminate\Support\Arr;
use Parse\Internal\Encodable;
use Parziphal\Parse\Relations\Relation;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

abstract class ObjectModel implements Arrayable, Jsonable
{
    protected static $parseClassName;
    
    /**
     * @var ReflectionProperty
     */
    protected static $serverDataProp;
    
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
    
    public static function create($data, $useMasterKey = false)
    {
        $model = new static($data, $useMasterKey);
        
        $model->save();
        
        return $model;
    }
    
    /**
     * Create a new query for this class.
     *
     * @return Query
     */
    public static function query($useMasterKey = false)
    {
        return new Query(static::parseClassName(), static::class, $useMasterKey);
    }
    
    /**
     * Static calls are passed to a new query.
     *
     * @return mixed
     */
    public static function __callStatic($method, array $params)
    {
        $query = static::query();
        
        return call_user_func_array([$query, $method], $params);
    }
    
    /**
     * @param ParseObject|array  $data
     * @param bool               $useMasterKey
     */
    public function __construct($data = null, $useMasterKey = false)
    {
        if ($data instanceof ParseObject) {
            $this->parseObject = $data;
        } else {
            $this->parseObject = new ParseObject(static::parseClassName());
            
            if (is_array($data)) {
                $this->fill($data);
            }
        }
        
        $this->useMasterKey = $useMasterKey;
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
        
        if (method_exists($this, $key)) {
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

        if (method_exists($this, $key)) {
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
    
    public function id()
    {
        return $this->parseObject->getObjectId();
    }
    
    public function update(array $data)
    {
        $this->fill($data)->save($this->useMasterKey);
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
        $arr = $this->toArrayDeep($this->getServerData());
        
        return $arr;
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
     * @return array
     */
    public function getServerData()
    {
        $data = $this->exposeServerData($this->parseObject);
        
        $data['id'] = $this->getObjectId();
        
        return $data;
    }
    
    /**
     * @param string  $key
     *
     * @return self
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
    
    protected function hasManyArray($otherClass, $key = null)
    {
        if (!$key) {
            $key = $this->getCallerFunctionName();
        }
        
        return new Relations\HasManyArray($otherClass, $key, $this);
    }
    
    protected function belongsTo($otherClass, $key = null)
    {
        if (!$key) {
            $key = $this->getCallerFunctionName();
        }
        
        return new Relations\BelongsTo($otherClass, $key, $this);
    }
    
    protected function hasMany($otherClass, $key = null)
    {
        if (!$key) {
            $key = lcfirst(static::parseClassName());
        }
        
        return new Relations\HasMany($otherClass::query(), $this, $key);
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
    
    /**
     * Helps with converting all relations into arrays.
     *
     * @param array $data
     *
     * @return array
     */
    protected function toArrayDeep(array $data)
    {
        $array = [];
        
        foreach ($data as $key => $value) {
            if ($value instanceof ParseObject) {
                $array[$key] = $this->toArrayDeep($this->exposeServerData($value));
            } elseif (is_array($value) || $value instanceof Traversable) {
                $array[$key] = $this->toArrayDeep($value);
            } else {
                $array[$key] = $value;
            }
        }
        
        return $array;
    }
    
    /**
     * Exposes the protected `serverData` property from a ParseObject.
     *
     * @return array
     */
    protected function exposeServerData(ParseObject $parseObject)
    {
        if (!self::$serverDataProp) {
            self::$serverDataProp = new ReflectionProperty(ParseObject::class, 'serverData');
            self::$serverDataProp->setAccessible(true);
        }

        return self::$serverDataProp->getValue($parseObject);
    }
}
