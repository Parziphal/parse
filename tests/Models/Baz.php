<?php

namespace Parziphal\Parse\Test\Models;

use Parziphal\Parse\ObjectModel;

class Baz extends ObjectModel
{
    public function foo()
    {
        return $this->belongsTo(Foo::class);
    }
}
