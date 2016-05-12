<?php

namespace Parziphal\Parse;

use Parse\ParseObject;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

class Model extends ParseObject implements Jsonable, Arrayable
{
    use ModelMethods;
    
    protected static $parseClassName;
}
