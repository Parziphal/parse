<?php

namespace Parziphal\Parse\Relations;

use Parziphal\Parse\ParseModel;

class BelongsTo extends Relation
{
    protected $embeddedClass;

    protected $keyName;

    protected $childObject;

    public function __construct($embeddedClass, $keyName, ParseModel $childObject)
    {
        $this->embeddedClass = $embeddedClass;
        $this->childObject = $childObject;
        $this->keyName = $keyName;
    }

    public function getResults()
    {
        $class = $this->embeddedClass;

        return (new $class($this->childObject->getParseObject ()->get ($this->keyName)))->fetch ();
    }
}
