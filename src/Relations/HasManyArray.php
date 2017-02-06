<?php

namespace Illuminate\Parse\Relations;

class HasManyArray extends HasMany
{
    public function addConstraints()
    {
        $this->query->containedIn ($this->foreignKey, $this->parentObject);
    }
}
