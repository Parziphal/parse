<?php

namespace Parziphal\Parse\Relations;

class HasManyArray extends HasMany
{
    public function addConstraints()
    {
        $this->query->containedIn ($this->foreignKey, $this->parentObject);
    }
}
