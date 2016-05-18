<?php

namespace Parziphal\Parse\Test\Models;

use Parziphal\Parse\ObjectModel;

class Foo extends ObjectModel
{
    public function bars()
    {
        return $this->hasManyArray(Bar::class);
    }
    
    public function bazs()
    {
        return $this->hasMany(Baz::class);
    }
}
