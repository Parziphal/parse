<?php

namespace Illuminate\Parse\Relations;

use Illuminate\Parse\Model;
use Illuminate\Parse\Query;

abstract class RelationWithQuery extends Relation
{
    protected $query;

    /**
     * @param Model
     */
    protected $parentObject;

    public function __construct(Query $query, Model $parentObject)
    {
        $this->query = $query;
        $this->parentObject = $parentObject;

        $this->addConstraints ();
    }

    abstract protected function addConstraints();

    public function __call($method, $parameters)
    {
        $result = call_user_func_array ([$this->query, $method], $parameters);

        if ($result === $this->query) {
            return $this;
        }

        return $result;
    }
}
