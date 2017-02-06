<?php

namespace Parziphal\Parse\Relations;

use Parziphal\Parse\ParseModel;
use Parziphal\Parse\Query;

abstract class RelationWithQuery extends Relation
{
    protected $query;

    /**
     * @param ParseModel
     */
    protected $parentObject;

    public function __construct(Query $query, ParseModel $parentObject)
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
