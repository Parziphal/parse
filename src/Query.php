<?php

namespace Parziphal\Parse;

use ReflectionClass;
use Parse\ParseQuery;
use Parse\ParseObject;

class Query extends ParseQuery
{
    protected static $orMethod;
    
    protected $fullClassName;
    
    public function setFullClassName($fullClassName)
    {
        if (!$this->fullClassName) {
            $this->fullClassName = $fullClassName;
        }
    }
    
    public function find($useMasterKey = false)
    {
        $className = $this->fullClassName;
        $models = [];
        
        foreach (parent::find($useMasterKey) as $object) {
            $models[] = $className::createExisting($object);
        }
        
        return new Collection($models);
    }
    
    /**
     * Mass-assigment of equalTo.
     *
     * ```
     * $model->where(['foo' => 1, 'bar' => 2])->first();
     * ```
     *
     * @return $this
     */
    public function where(array $equalTos)
    {
        foreach ($equalTos as $key => $value) {
            $this->equalTo($key, $value);
        }
        
        return $this;
    }
    
    /**
     * ParseQuery calls `self` instead of `static` in this one.
     *
     * @param array $queryObjects
     *
     * @return ParseQuery
     */
    public static function orQueries($queryObjects)
    {
        // Allow parent to do the checks.
        parent::orQueries($queryObjects);
        
        $class = $queryObjects[0]->fullClassName;
        
        $query = new static($class::parseClassName());
        $query->setFullClassName($class);
        
        if (!self::$orMethod) {
            self::$orMethod = (new ReflectionClass(ParseQuery::class))->getMethod('_or');
            self::$orMethod->setAccessible(true);
        }
        
        self::$orMethod->invoke($query, $queryObjects);

        return $query;
    }
}
