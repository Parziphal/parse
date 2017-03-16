<?php

namespace Parziphal\Parse\Relations;

use Parziphal\Parse\Query;
use Parziphal\Parse\ObjectModel;

abstract class HasOneOrMany extends RelationWithQuery
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

    /**
     * Create a new child object, and relate it to this.
     *
     * @param  array        $data
     * @return ObjectModel
     */
    public function create(array $data)
    {
        $class = $this->query->getFullClassName();

        $model = new $class($data);

        return $this->save($model);
    }

    /**
     * Relate other object to this object.
     *
     * @param  ObjectModel $model The child object
     * @return ObjectModel
     */
    public function save(ObjectModel $model)
    {
        $model->{$this->foreignKey} = $this->parentObject;

        $model->save();

        return $model;
    }
}
