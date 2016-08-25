<?php

namespace Parziphal\Parse\Relations;

use Parziphal\Parse\Query;
use Parziphal\Parse\ObjectModel;

class HasManyArray extends HasMany
{
    public function addConstraints()
    {
        $this->query->containedIn($this->foreignKey, $this->parentObject);
    }
}
