<?php

namespace Parziphal\Parse\Relations;

class HasOne extends HasOneOrMany
{
    public function getResults()
    {
        return $this->query->first();
    }
}
