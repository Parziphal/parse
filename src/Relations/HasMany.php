<?php

namespace Parziphal\Parse\Relations;

use Parziphal\Parse\ParseModel;
use Parziphal\Parse\Query;

class HasMany extends RelationWithQuery
{
    protected $foreignKey;

    public function __construct(Query $query, ParseModel $parentObject, $foreignKey)
    {
        $this->foreignKey = $foreignKey;

        parent::__construct ($query, $parentObject);
    }

    public function addConstraints()
    {
        $this->query->where ($this->foreignKey, $this->parentObject);
    }

    public function getResults()
    {
        return $this->query->get ();
    }

    /**
     * Create a new child object, and relate it to this.
     *
     * @param  array $data
     * @return ParseModel
     */
    public function create(array $data)
    {
        $class = $this->query->getFullClassName ();

        $model = new $class($data);

        return $this->save ($model);
    }

    /**
     * Relate other object to this object.
     *
     * @param  ParseModel $model The child object
     * @return ParseModel
     */
    public function save(ParseModel $model)
    {
        $model->{$this->foreignKey} = $this->parentObject;

        $model->save ();

        return $model;
    }
}
