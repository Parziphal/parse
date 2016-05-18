<?php

namespace Parziphal\Parse\Test\Models;

use Parziphal\Parse\ObjectModel;

class Bar extends ObjectModel
{
    public function foo()
    {
        return $this->belongsTo(Foo::class);
    }
}
