<?php

namespace Parziphal\Parse\Relations;

use Parziphal\Parse\Query;
use Parziphal\Parse\ObjectModel;

class HasMany extends RelationWithQuery
{
    protected $foreignKey;
    
    public function __construct(Query $query, ObjectModel $parentObject, $foreignKey)
    {
        $this->foreignKey = $foreignKey;
        
        parent::__construct($query, $parentObject);
    }
    
    public function addConstraints()
    {
        $this->query->where($this->foreignKey, $this->parentObject);
    }
    
    public function getResults()
    {
        return $this->query->get();
    }
    
    public function create(array $data)
    {
        $class = $this->query->getFullClassName();
        
        $model = new $class($data);
        
        return $this->save($model);
    }
    
    public function save(ObjectModel $model)
    {
        $model->{$this->foreignKey} = $this->parentObject;
        
        $model->save();
        
        return $model;
    }
}
