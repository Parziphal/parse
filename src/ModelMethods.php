<?php

namespace Parziphal\Parse;

use ReflectionClass;
use Traversable;
use Parse\ParseObject;
use Parse\Internal\Encodable;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Any class that uses this trait must define a
 * static property called `paseClassName`.
 */
trait ModelMethods
{
    protected static $booted = [];
    
    private static $mergeMethod;
    
    /**
     * Create a new model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct($className = null, $objectId = null, $isPointer = false)
    {
        $this->bootIfNotBooted();
        
        parent::__construct(static::parseClassName(), $objectId, $isPointer);
    }

    /**
     * Check if the model needs to be booted and if so, do it.
     *
     * @return void
     */
    protected function bootIfNotBooted()
    {
        $class = get_class($this);

        if (! isset(static::$booted[$class])) {
            static::$booted[$class] = true;

            static::boot();
        }
    }

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        if (!self::$mergeMethod) {
            $reflection = new ReflectionClass(ParseObject::class);
            
            $merge = $reflection->getMethod('mergeFromObject');
            
            $merge->setAccessible(true);
            
            self::$mergeMethod = $merge;
        }
        
        self::registerSubclass();
    }
    
    public static function shortName()
    {
        return substr(static::class, strrpos(static::class, '\\') + 1);
    }
    
    public static function parseClassName()
    {
        return static::$parseClassName ?: static::shortName();
    }
    
    public static function registerSubclass()
    {
        if (static::$parseClassName !== null) {
            parent::registerSubclass();
        } else {
            static::$parseClassName = static::parseClassName();
            
            parent::registerSubclass();
            
            static::$parseClassName = null;
        }
    }
    
    public static function query()
    {
        $query = new Query(static::parseClassName());
        
        $query->setFullClassName(static::class);
        
        return $query;
    }
    
    public static function all()
    {
        return static::query()->find();
    }
    
    /**
     * Create an instance of a subclass out of a different ParseObject.
     *
     * @return static
     */
    public static function createExisting(ParseObject $object)
    {
        $model = new static(static::parseClassName(), $object->getObjectId(), true);
        
        self::$mergeMethod->invoke($model, $object);
        
        return $model;
    }
    
    public static function find($id)
    {
        return static::query()->get($id, true);
    }
    
    public static function findOrFail($id)
    {
        $model = static::find($id);
        
        if (!$model) {
            $e = new ModelNotFoundException();
            $e->setModel(static::parseClassName());
            
            throw $e;
        }
        
        return $model;
    }
    
    /**
     * Equivalent to `$storedModel = MyModel::create($data);`.
     * The name `create` has already been used by ParseObject;
     * this method can be used instead to create a new instance
     * and immediately save it to the database.
     *
     * @return static
     */
    public static function store(array $data)
    {
        $model = new static();
        
        $model->fill($data)->save();
        
        return $model;
    }
    
    /**
     * Equivalent to `$newModel = new MyModel($data);`.
     * Since the __construct doesn't accept new data as
     * arguments, this method can be used instead, to
     * create a new non-empty instance.
     */
    public static function fillNew(array $data)
    {
        $model = new static();
        
        return $model->fill($data);
    }
    
    public static function __callStatic($method, array $params)
    {
        return call_user_func_array([static::query(), $method], $params);
    }
    
    /**
     * @return bool
     */
    public function update(array $data, $userMasterKey = false)
    {
        return $this->fill($data)->save($userMasterKey);
    }
    
    /**
     * @return $this
     */
    public function fill(array $data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (Arr::isAssoc($value)) {
                    $this->setAssociativeArray($key, $value);
                } else {
                    $this->setArray($key, $value);
                }
            } else {
                $this->set($key, $value);
            }
        }
        
        return $this;
    }
    
    /**
     * @return array
     */
    public function toArray()
    {
        $arr = $this->toArrayRecursive($this->serverData);
        
        $arr['id'] = $this->getObjectId();
        
        return $arr;
    }
    
    public function getServerData()
    {
        return $this->serverData();
    }
    
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }
    
    protected function toArrayRecursive(array $data)
    {
        $array = [];
        
        foreach ($data as $key => $value) {
            if ($value instanceof Encodable) {
                if ($value instanceof ParseObject) {
                    $array[$key] = $value->serverData;
                } else {
                    $array[$key] = $value->_encode();
                }
            } elseif (is_array($value) || $value instanceof Traversable) {
                $array[$key] = $this->toArrayRecursive($value);
            } else {
                $array[$key] = $value;
            }
        }
        
        return $array;
    }
}
