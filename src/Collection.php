<?php

namespace Parziphal\Parse;

use Illuminate\Support\Collection as BaseCollection;

class Collection extends BaseCollection
{
    public function toArray()
    {
        $array = [];
        
        foreach ($this->items as $item) {
            $array[] = $item->toArray();
        }
        
        return $array;
    }
}
